<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Toast } from '@/components/ui/toast';
import { 
    Dialog, 
    DialogTrigger, 
    DialogContent, 
    DialogHeader, 
    DialogTitle,
    DialogDescription,
    DialogFooter 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { CheckCircle, AlertCircle, X, LockIcon } from 'lucide-vue-next';
import Swal from 'sweetalert2';

interface Membresia {
    id: number;
    nombre: string;
    precio: number;
    porcentaje_rendimiento: number;
    porcentaje_comision_sponsor: number;
    comision_directa: number;
}

interface Props {
    membresias: Membresia[];
    user: {
        id: number;
        name: string;
        email: string;
        membership_id: number | null;
        membership_expires_at: string | null;
        capital: number; // Saldo en capital del usuario
    };
    activeMembership: Membresia | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Seleccionar Membresía',
        href: '/membership/select',
    }
];

const selectedMembership = ref<Membresia | null>(null);
const showPaymentDialog = ref(false);
const showConfirmationDialog = ref(false);
const showBlockedDialog = ref(false);
const showRenewalConfirmationDialog = ref(false);

// Formulario para activación directa (membresías gratuitas)
const directForm = useForm({
    membresia_id: null as number | null
});

// Formulario para membresías con pago
const paymentForm = useForm({
    membresia_id: null as number | null,
    bank_name: '',
    transaction_reference: '',
    receipt_image: null as File | null,
    notes: '',
    is_renewal: false
});

// Determinar si el usuario puede cambiar de membresía
const canChangeMembership = computed(() => {
    // Si no hay membresía activa, el usuario puede seleccionar cualquiera
    if (!props.activeMembership) return true;

    // Si la membresía está expirada, puede cambiar a cualquiera
    if (isExpired()) return true;

    // Si la membresía actual es gratuita y el capital es 0, el usuario puede cambiar
    if (props.activeMembership.precio <= 0 && props.user.capital <= 0) return true;

    // Si quedan más de 15 días, no puede cambiar a ninguna
    const remainingDays = getRemainingDays() || 0;
    if (remainingDays > 15) return false;

    // Si quedan menos de 10 días, solo puede renovar la misma membresía
    return remainingDays <= 10;
});

// Determinar si puede renovar la misma membresía
const canRenewSameMembership = computed(() => {
    // Si no hay membresía activa, no hay nada que renovar
    if (!props.activeMembership) return false;
    
    // Solo se puede renovar si quedan menos de 10 días
    const remainingDays = getRemainingDays() || 0;
    return remainingDays <= 10 && remainingDays > 0;
});

// Verificar si la membresía seleccionada es la misma que la actual
const isSameMembership = computed(() => {
    if (!selectedMembership.value || !props.activeMembership) return false;
    return selectedMembership.value.id === props.activeMembership.id;
});

// Verificar si se permite seleccionar una membresía específica
const canSelectMembership = (membresia: Membresia) => {
    // Si no hay membresía activa, puede seleccionar cualquiera
    if (!props.activeMembership) return true;

    // Si está expirada, puede seleccionar cualquiera
    if (isExpired()) return true;

    // Si la membresía actual es gratuita y el capital es 0, puede seleccionar cualquiera
    if (props.activeMembership.precio <= 0 && props.user.capital <= 0) return true;

    // Si quedan más de 15 días, no puede seleccionar ninguna
    const remainingDays = getRemainingDays() || 0;
    if (remainingDays > 15) return false;

    // Si quedan menos de 10 días, solo puede renovar la misma membresía
    if (remainingDays <= 10) {
        return membresia.id === props.activeMembership.id;
    }

    // Para casos entre 10-15 días, no puede seleccionar ninguna
    return false;
};

const selectMembership = (membresia: Membresia) => {
    // Verificar si puede seleccionar esta membresía
    if (!canSelectMembership(membresia)) {
        if (props.activeMembership && getRemainingDays() && getRemainingDays()! > 15) {
            // Si quedan más de 15 días, mostrar mensaje específico
            Swal.fire({
                title: 'No permitido',
                text: 'No puedes cambiar de membresía cuando te quedan más de 15 días en tu membresía actual.',
                icon: 'warning'
            });
        } else if (props.activeMembership && getRemainingDays() && getRemainingDays()! > 10 && getRemainingDays()! <= 15) {
            // Si quedan entre 10-15 días
            Swal.fire({
                title: 'No permitido',
                text: 'Debes esperar a que te queden 10 días o menos para renovar tu membresía.',
                icon: 'warning'
            });
        } else {
            // Mostrar diálogo general de bloqueo
            showBlockedDialog.value = true;
        }
        return;
    }

    selectedMembership.value = membresia;
    directForm.membresia_id = membresia.id;
    paymentForm.membresia_id = membresia.id;
};

const handleSubmit = () => {
    if (!selectedMembership.value) {
        Toast.error({
            title: 'Error',
            description: 'Por favor seleccione una membresía'
        });
        return;
    }

    // Si está renovando la misma membresía
    if (props.activeMembership && props.activeMembership.id === selectedMembership.value.id && !isExpired()) {
        if (canRenewSameMembership.value) {
            // Mostrar diálogo de confirmación de renovación
            showRenewalConfirmationDialog.value = true;
        } else {
            Swal.fire({
                title: 'No permitido',
                text: 'Solo puedes renovar tu membresía cuando te queden 10 días o menos.',
                icon: 'warning'
            });
        }
        return;
    }

    // Verificar si puede cambiar de membresía
    if (props.activeMembership && props.activeMembership.id !== selectedMembership.value.id) {
        if (!canChangeMembership.value) {
            showBlockedDialog.value = true;
            return;
        }
        
        showConfirmationDialog.value = true;
        return;
    }

    proceedWithMembershipSelection();
};

const proceedWithMembershipSelection = () => {
    // Cerrar todos los diálogos si están abiertos
    showConfirmationDialog.value = false;
    showRenewalConfirmationDialog.value = false;
    
    // Si la membresía tiene costo, mostrar diálogo para subir comprobante
    // Si no tiene costo, activar directamente
    if (selectedMembership.value && selectedMembership.value.precio > 0) {
        showPaymentDialog.value = true;
    } else {
        // Usar SweetAlert2 directamente en lugar de Toast.loading
        const loadingAlert = Swal.fire({
            title: 'Procesando',
            text: 'Activando membresía...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Enviar la solicitud de activación de membresía
        directForm.post(route('membership.activate'), {
            preserveScroll: true,
            onSuccess: (response) => {
                // Cerrar el alert de carga
                loadingAlert.close();
                
                // Mostrar mensaje de éxito
                Swal.fire({
                    title: 'Éxito',
                    text: 'La membresía ha sido activada correctamente',
                    icon: 'success'
                }).then(() => {
                    // Redireccionar al dashboard
                    window.location.href = route('dashboard');
                });
            },
            onError: (errors) => {
                // Cerrar el alert de carga
                loadingAlert.close();
                
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: errors.membresia_id || 'Ocurrió un error al activar la membresía',
                    icon: 'error'
                });
                
                console.error('Error activando membresía:', errors);
            },
            onFinish: () => {
                // Si por alguna razón el alert sigue abierto, cerrarlo
                if (Swal.isVisible()) {
                    loadingAlert.close();
                }
            }
        });
    }
};

const submitPayment = () => {
    // Usar SweetAlert2 directamente en lugar de Toast.loading
    const loadingAlert = Swal.fire({
        title: 'Procesando',
        text: isSameMembership.value && canRenewSameMembership.value ? 'Enviando comprobante de renovación...' : 'Enviando comprobante de pago...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Indicar en la solicitud si es una renovación
    if (isSameMembership.value && canRenewSameMembership.value) {
        paymentForm.is_renewal = true;
    } else {
        paymentForm.is_renewal = false;
    }
    
    paymentForm.post(route('membership.pay'), {
        preserveScroll: true,
        onSuccess: (response) => {
            // Cerrar el alert de carga
            loadingAlert.close();
            
            // Cerrar el diálogo de pago
            showPaymentDialog.value = false;
            
            // Mostrar mensaje de éxito
            let successMessage = isSameMembership.value && canRenewSameMembership.value 
                ? 'Su solicitud de renovación de membresía ha sido enviada y está pendiente de aprobación'
                : 'Su solicitud de activación de membresía ha sido enviada y está pendiente de aprobación';
                
            Swal.fire({
                title: isSameMembership.value && canRenewSameMembership.value ? 'Renovación solicitada' : 'Comprobante enviado',
                text: successMessage,
                icon: 'success'
            }).then(() => {
                // Redireccionar al historial
                window.location.href = route('membership.history');
            });
            
            // Resetear el formulario
            paymentForm.reset();
        },
        onError: (errors) => {
            // Cerrar el alert de carga
            loadingAlert.close();
            
            // Mostrar mensaje de error más descriptivo
            let errorMsg = 'Ocurrió un error al procesar el pago';
            
            if (errors.membresia_id) errorMsg = errors.membresia_id;
            else if (errors.bank_name) errorMsg = errors.bank_name;
            else if (errors.transaction_reference) errorMsg = errors.transaction_reference;
            else if (errors.receipt_image) errorMsg = errors.receipt_image;
            
            Swal.fire({
                title: 'Error',
                text: errorMsg,
                icon: 'error'
            });
            
            console.error('Error procesando pago:', errors);
        },
        onFinish: () => {
            // Si por alguna razón el alert sigue abierto, cerrarlo
            if (Swal.isVisible()) {
                loadingAlert.close();
            }
        }
    });
};

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    paymentForm.receipt_image = file;
};

// Formatear montos a moneda
const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
};

// Calcular días restantes de membresía
const getRemainingDays = (): number | null => {
    if (!props.user.membership_expires_at) return null;
    
    const expirationDate = new Date(props.user.membership_expires_at);
    const now = new Date();
    
    if (now > expirationDate) return 0;
    
    const diffTime = Math.abs(expirationDate.getTime() - now.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const isExpired = (): boolean => {
    if (!props.user.membership_expires_at) return false;
    const expirationDate = new Date(props.user.membership_expires_at);
    const now = new Date();
    return now > expirationDate;
};
</script>

<template>
    <Head title="Seleccionar Membresía" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Información sobre membresía actual -->
                <div v-if="activeMembership" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-xl font-bold mb-2">Membresía Actual</h2>
                                <p class="text-gray-700">Actualmente tienes la membresía <span class="font-semibold">{{ activeMembership.nombre }}</span>.</p>
                                
                                <div v-if="user.membership_expires_at" class="mt-2">
                                    <p v-if="isExpired()" class="text-red-600">
                                        <AlertCircle class="inline-block w-5 h-5 mr-1" />
                                        Tu membresía ha expirado. Selecciona una nueva membresía para continuar.
                                    </p>
                                    <p v-else class="text-gray-700">
                                        Expira en <span class="font-semibold" :class="{ 
                                            'text-green-600': getRemainingDays() > 30,
                                            'text-yellow-600': getRemainingDays() <= 30 && getRemainingDays() > 10,
                                            'text-orange-600': getRemainingDays() <= 10 && getRemainingDays() > 5,
                                            'text-red-600': getRemainingDays() <= 5
                                        }">{{ getRemainingDays() }} días</span>.
                                        
                                        <span v-if="canRenewSameMembership" class="ml-2 text-blue-600 text-sm">
                                            <CheckCircle class="inline-block w-4 h-4 mr-1" />
                                            Puedes renovar esta membresía
                                        </span>
                                    </p>
                                </div>
                                
                                <div v-if="!canChangeMembership && !canRenewSameMembership" class="mt-2 text-red-600">
                                    <AlertCircle class="inline-block w-5 h-5 mr-1" />
                                    <span v-if="getRemainingDays() > 15">
                                        No puedes cambiar tu membresía cuando te quedan más de 15 días.
                                    </span>
                                    <span v-else-if="getRemainingDays() > 10">
                                        Debes esperar a que te queden 10 días o menos para renovar tu membresía.
                                    </span>
                                    <span v-else>
                                        No puedes cambiar tu membresía actual mientras esté activa y tenga un costo asociado.
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 mt-2">
                                    Capital actual: <span class="font-semibold">{{ formatCurrency(user.capital) }}</span>
                                    <Button variant="link" class="p-0 text-blue-600 ml-2" @click="$inertia.visit('/membership/history')">
                                        Ver historial
                                    </Button>
                                </p>
                            </div>
                            
                            <div class="bg-blue-50 px-4 py-3 rounded-lg">
                                <p class="text-sm text-blue-700 font-medium">Detalles</p>
                                <p class="text-lg font-bold text-blue-800">{{ formatCurrency(activeMembership.precio) }}</p>
                                <p class="text-sm text-blue-600">Rendimiento: {{ activeMembership.porcentaje_rendimiento }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información general -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h1 class="text-2xl font-bold mb-2">Selección de Membresía</h1>
                        <p class="text-lg">Para continuar, selecciona una membresía que se ajuste a tus necesidades.</p>
                        
                        <Alert v-if="activeMembership && canChangeMembership && !canRenewSameMembership" class="mt-4">
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>Información importante</AlertTitle>
                            <AlertDescription>
                                Al seleccionar una nueva membresía, tu membresía actual pasará al historial una vez que el pago sea aprobado.
                            </AlertDescription>
                        </Alert>
                        
                        <Alert v-if="activeMembership && canRenewSameMembership" class="mt-4 bg-blue-50 border-blue-200">
                            <CheckCircle class="h-4 w-4 text-blue-600" />
                            <AlertTitle>Renovación disponible</AlertTitle>
                            <AlertDescription>
                                Ahora puedes renovar tu membresía actual. Al renovar, se agregarán 30 días más a tu tiempo restante.
                            </AlertDescription>
                        </Alert>
                        
                        <Alert v-if="activeMembership && !canChangeMembership && !canRenewSameMembership" class="mt-4 bg-red-50 border-red-200">
                            <LockIcon class="h-4 w-4" />
                            <AlertTitle>Restricción de cambio</AlertTitle>
                            <AlertDescription v-if="getRemainingDays() > 15">
                                No es posible cambiar de membresía cuando te quedan más de 15 días en tu membresía actual.
                            </AlertDescription>
                            <AlertDescription v-else-if="getRemainingDays() > 10">
                                Debes esperar a que te queden 10 días o menos para renovar tu membresía.
                            </AlertDescription>
                            <AlertDescription v-else>
                                No es posible cambiar de membresía mientras tengas una membresía activa con costo.
                            </AlertDescription>
                        </Alert>
                    </div>
                </div>

                <!-- Lista de membresías -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="membresia in membresias" :key="membresia.id" 
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg cursor-pointer border-2 relative"
                        :class="{
                            'border-indigo-500': selectedMembership && selectedMembership.id === membresia.id,
                            'border-transparent': !(selectedMembership && selectedMembership.id === membresia.id),
                            'border-blue-500': activeMembership && activeMembership.id === membresia.id && canRenewSameMembership,
                            'opacity-50 pointer-events-none': activeMembership && !canSelectMembership(membresia)
                        }"
                        @click="selectMembership(membresia)">
                        
                        <!-- Indicador de membresía actual -->
                        <div v-if="activeMembership && activeMembership.id === membresia.id" 
                            class="absolute top-0 right-0 bg-green-500 text-white px-3 py-1 text-sm">
                            <CheckCircle class="inline-block w-4 h-4 mr-1" />
                            Actual
                        </div>
                        
                        <!-- Indicador de renovación disponible -->
                        <div v-if="activeMembership && activeMembership.id === membresia.id && canRenewSameMembership" 
                            class="absolute top-8 right-0 bg-blue-500 text-white px-3 py-1 text-sm">
                            <CheckCircle class="inline-block w-4 h-4 mr-1" />
                            Renovable
                        </div>
                        
                        <!-- Indicador de membresía bloqueada -->
                        <div v-if="activeMembership && !canSelectMembership(membresia) && activeMembership.id !== membresia.id" 
                            class="absolute top-0 right-0 bg-red-500 text-white px-3 py-1 text-sm">
                            <LockIcon class="inline-block w-4 h-4 mr-1" />
                            Bloqueada
                        </div>
                        
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2">{{ membresia.nombre }}</h3>
                            <p class="text-2xl font-semibold mb-4">{{ formatCurrency(membresia.precio) }}</p>
                            <div class="space-y-2 mb-4">
                                <p><span class="font-medium">Rendimiento:</span> {{ membresia.porcentaje_rendimiento }}%</p>
                                <p><span class="font-medium">Comisión al sponsor:</span> {{ membresia.porcentaje_comision_sponsor }}%</p>
                                <p><span class="font-medium">Comisión directa al sponsor:</span> {{ formatCurrency(membresia.comision_directa) }}</p>
                            </div>
                            
                            <!-- Etiqueta para membresías gratuitas -->
                            <div v-if="membresia.precio <= 0" class="mt-4 text-green-600 font-medium">
                                Activación inmediata (sin costo)
                            </div>
                            <!-- Etiqueta para membresías con costo -->
                            <div v-else class="mt-4 text-blue-600 font-medium">
                                Requiere comprobante de pago
                            </div>
                            
                            <!-- Etiqueta de renovación -->
                            <div v-if="activeMembership && activeMembership.id === membresia.id && canRenewSameMembership" 
                                class="mt-2 text-blue-600 font-medium">
                                Renovar (+30 días)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <button 
                        @click="handleSubmit"
                        :disabled="!selectedMembership || directForm.processing || paymentForm.processing || (activeMembership && !canSelectMembership(selectedMembership))"
                        class="bg-indigo-500 text-white px-6 py-3 rounded-md hover:bg-indigo-600 transition-colors disabled:bg-gray-300"
                    >
                        <span v-if="isSameMembership && canRenewSameMembership">
                            {{ directForm.processing || paymentForm.processing ? 'Procesando...' : 'Renovar Membresía' }}
                        </span>
                        <span v-else>
                            {{ directForm.processing || paymentForm.processing ? 'Procesando...' : 'Activar Membresía Seleccionada' }}
                        </span>
                    </button>
                    
                    <div v-if="directForm.errors.membresia_id" class="text-red-500 mt-2">
                        {{ directForm.errors.membresia_id }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Diálogo de confirmación para renovar misma membresía -->
        <Dialog :open="showRenewalConfirmationDialog" @update:open="showRenewalConfirmationDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Renovar membresía actual</DialogTitle>
                    <DialogDescription>
                        Estás a punto de renovar tu membresía actual ({{ activeMembership?.nombre }}).
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <p class="text-gray-700">
                        Al renovar, se añadirán 30 días adicionales a tu membresía actual. 
                        Actualmente te quedan <span class="font-semibold">{{ getRemainingDays() }} días</span>, 
                        por lo que tendrás un total de <span class="font-semibold text-green-600">{{ getRemainingDays() + 30 }} días</span>.
                    </p>
                    
                    <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-4">
                        <p class="text-blue-700">
                            <CheckCircle class="inline-block w-5 h-5 mr-1" />
                            La renovación mantendrá activos todos los beneficios de tu membresía actual.
                        </p>
                    </div>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showRenewalConfirmationDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="proceedWithMembershipSelection">
                        Confirmar renovación
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Diálogo de confirmación para cambiar membresía -->
        <Dialog :open="showConfirmationDialog" @update:open="showConfirmationDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>¿Cambiar de membresía?</DialogTitle>
                    <DialogDescription>
                        Estás a punto de cambiar tu membresía actual ({{ activeMembership?.nombre }}) 
                        por una nueva membresía ({{ selectedMembership?.nombre }}).
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <p class="text-gray-700">
                        Tu membresía actual pasará al historial cuando la nueva membresía sea aprobada.
                    </p>
                    
                    <div class="mt-4 bg-amber-50 border-l-4 border-amber-400 p-4">
                        <p class="text-amber-700">
                            <AlertCircle class="inline-block w-5 h-5 mr-1" />
                            La membresía anterior podría perder sus beneficios una vez realizado el cambio.
                        </p>
                    </div>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showConfirmationDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="proceedWithMembershipSelection">
                        Continuar con el cambio
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Diálogo para membresías con pago -->
        <Dialog :open="showPaymentDialog" @update:open="showPaymentDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ isSameMembership && canRenewSameMembership ? 'Renovación de membresía' : 'Comprobante de pago para membresía' }}
                    </DialogTitle>
                    <DialogDescription>
                        <span v-if="isSameMembership && canRenewSameMembership">
                            Para renovar tu membresía {{ selectedMembership?.nombre }}, debes pagar {{ formatCurrency(selectedMembership?.precio) }}
                            y subir el comprobante correspondiente.
                        </span>
                        <span v-else>
                            Para activar la membresía {{ selectedMembership?.nombre }}, debes pagar {{ formatCurrency(selectedMembership?.precio) }} 
                            y subir el comprobante correspondiente.
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <div class="grid gap-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="bank_name" class="text-right">Banco/Método:</Label>
                            <Input 
                                id="bank_name" 
                                v-model="paymentForm.bank_name" 
                                class="col-span-3" 
                                placeholder="Nombre del banco o método de pago"
                                :class="{ 'border-red-500': paymentForm.errors.bank_name }"
                            />
                            <div v-if="paymentForm.errors.bank_name" class="text-red-500 text-sm col-span-3 col-start-2">
                                {{ paymentForm.errors.bank_name }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="transaction_reference" class="text-right">Referencia:</Label>
                            <Input 
                                id="transaction_reference" 
                                v-model="paymentForm.transaction_reference" 
                                class="col-span-3"
                                placeholder="Número de referencia de la transacción"
                                :class="{ 'border-red-500': paymentForm.errors.transaction_reference }"
                            />
                            <div v-if="paymentForm.errors.transaction_reference" class="text-red-500 text-sm col-span-3 col-start-2">
                                {{ paymentForm.errors.transaction_reference }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-4 items-start gap-4">
                            <Label for="receipt_image" class="text-right pt-2">Comprobante:</Label>
                            <div class="col-span-3">
                                <Input 
                                    id="receipt_image" 
                                    type="file" 
                                    @input="handleImageUpload"
                                    accept="image/*"
                                    :class="{ 'border-red-500': paymentForm.errors.receipt_image }"
                                />
                                <div class="text-xs text-gray-500 mt-1">
                                    Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                                </div>
                                <div v-if="paymentForm.errors.receipt_image" class="text-red-500 text-sm mt-1">
                                    {{ paymentForm.errors.receipt_image }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-4 items-start gap-4">
                            <Label for="notes" class="text-right pt-2">Notas:</Label>
                            <Textarea 
                                id="notes" 
                                v-model="paymentForm.notes" 
                                class="col-span-3"
                                placeholder="Información adicional sobre el pago"
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showPaymentDialog = false">Cancelar</Button>
                    <Button 
                        @click="submitPayment" 
                        :disabled="paymentForm.processing"
                    >
                        {{ paymentForm.processing ? 'Enviando...' : (isSameMembership && canRenewSameMembership ? 'Enviar comprobante de renovación' : 'Enviar comprobante') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Diálogo de membresía bloqueada -->
        <Dialog :open="showBlockedDialog" @update:open="showBlockedDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cambio de membresía no permitido</DialogTitle>
                </DialogHeader>
                
                <div class="py-4">
                    <div class="bg-red-50 border-l-4 border-red-400 p-4">
                        <p class="text-red-700">
                            <AlertCircle class="inline-block w-5 h-5 mr-1" />
                            <span v-if="getRemainingDays() > 15">
                                No puedes cambiar tu membresía cuando te quedan más de 15 días.
                            </span>
                            <span v-else-if="getRemainingDays() > 10">
                                Debes esperar a que te queden 10 días o menos para renovar tu membresía.
                            </span>
                            <span v-else>
                                No puedes cambiar de membresía mientras tengas una membresía activa de pago.
                            </span>
                        </p>
                    </div>
                    
                    <p class="mt-4 text-gray-700">
                        Solo es posible cambiar de membresía cuando:
                    </p>
                    <ul class="list-disc pl-5 mt-2 text-gray-700">
                        <li>Tu membresía actual no tenga costo (gratuita) y tu capital sea 0.</li>
                        <li>Tu membresía actual haya expirado.</li>
                        <li>Te queden 10 días o menos para renovar la misma membresía.</li>
                    </ul>
                </div>
                
                <DialogFooter>
                    <Button @click="showBlockedDialog = false">
                        Entendido
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template> 