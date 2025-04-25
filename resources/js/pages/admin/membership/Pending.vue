<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { 
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle,
    DialogDescription,
    DialogFooter 
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { 
    CheckCircle, 
    XCircle, 
    Eye,
    CreditCard,
    Info,
    AlertCircle,
    User,
    FileText,
    Calendar,
    DollarSign
} from 'lucide-vue-next';
import { Toast } from '@/components/ui/toast';

interface User {
    id: number;
    name: string;
    email: string;
    pending_membership_id: number;
    pendingMembership: {
        id: number;
        nombre: string;
        precio: number;
        comision_directa: number;
    };
    sponsor?: {
        id: number;
        name: string;
        email: string;
    };
    membership_payment_info?: {
        payment_method: string;
        payment_reference: string;
        receipt_image: string;
        notes: string;
        created_at: string;
    };
}

interface Deposit {
    id: number;
    user_id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    amount: number;
    bank_name: string;
    transaction_reference: string;
    receipt_image: string;
    notes: string;
    created_at: string;
}

const props = defineProps<{
    pendingMemberships: User[];
    pendingDeposits: Deposit[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de Administración',
        href: '/admin/dashboard',
    },
    {
        title: 'Membresías Pendientes',
        href: '/admin/memberships/pending',
    }
];

const showRejectDialog = ref(false);
const showApproveDialog = ref(false);
const showDetailDialog = ref(false);
const selectedUser = ref<User | null>(null);
const showReceiptDialog = ref(false);
const selectedReceiptUrl = ref<string>('');
const isLoading = ref<Record<number, boolean>>({});
const loadingRejection = ref(false);
const loadingApproval = ref(false);

const rejectForm = useForm({
    reason: '',
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
};

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getUserDeposit = (userId: number): Deposit | undefined => {
    // Primero buscar en depósitos pendientes (la forma actual)
    const deposit = props.pendingDeposits.find(d => d.user_id === userId);
    
    // Si encontramos un depósito con comprobante, lo devolvemos
    if (deposit?.receipt_image) {
        return deposit;
    }
    
    // Si no hay depósito con comprobante, debemos buscar en la información de pago del usuario
    const user = props.pendingMemberships.find(u => u.id === userId);
    
    // Si existe el usuario y tiene información de pago con comprobante, creamos un objeto deposit virtual
    if (user?.membership_payment_info?.receipt_image) {
        return {
            id: 0, // ID simulado
            user_id: userId,
            user: {
                id: userId,
                name: user.name,
                email: user.email
            },
            amount: user.pendingMembership?.precio || 0,
            bank_name: user.membership_payment_info.payment_method || 'No especificado',
            transaction_reference: user.membership_payment_info.payment_reference || 'No especificado',
            receipt_image: user.membership_payment_info.receipt_image || '',
            notes: user.membership_payment_info.notes || '',
            created_at: user.membership_payment_info.created_at || ''
        };
    }
    
    // Si no encontramos nada, retornamos undefined
    return undefined;
};

const openRejectDialog = (user: User) => {
    selectedUser.value = user;
    showRejectDialog.value = true;
    rejectForm.reset();
};

const openApproveDialog = (user: User) => {
    selectedUser.value = user;
    showApproveDialog.value = true;
};

const openDetailDialog = (user: User) => {
    selectedUser.value = user;
    showDetailDialog.value = true;
};

const rejectMembership = () => {
    if (!selectedUser.value) return;
    
    loadingRejection.value = true;
    rejectForm.post(route('admin.memberships.reject', { user: selectedUser.value.id }), {
        onSuccess: () => {
            showRejectDialog.value = false;
            loadingRejection.value = false;
            Toast.success({
                title: 'Membresía rechazada',
                description: 'La membresía ha sido rechazada exitosamente'
            });
        },
        onError: () => {
            loadingRejection.value = false;
            Toast.error({
                title: 'Error',
                description: 'Ha ocurrido un error al rechazar la membresía'
            });
        }
    });
};

const approveMembership = () => {
    if (!selectedUser.value) return;
    
    loadingApproval.value = true;
    isLoading.value[selectedUser.value.id] = true;
    
    const form = useForm({});
    form.post(route('admin.memberships.approve', { user: selectedUser.value.id }), {
        onSuccess: () => {
            isLoading.value[selectedUser.value.id] = false;
            loadingApproval.value = false;
            showApproveDialog.value = false;
            
            Toast.success({
                title: 'Membresía aprobada',
                description: 'La membresía ha sido aprobada exitosamente'
            });
        },
        onError: () => {
            isLoading.value[selectedUser.value.id] = false;
            loadingApproval.value = false;
            showApproveDialog.value = false;
            
            Toast.error({
                title: 'Error',
                description: 'Ha ocurrido un error al aprobar la membresía'
            });
        }
    });
};

const showReceipt = (imageUrl: string) => {
    selectedReceiptUrl.value = imageUrl;
    showReceiptDialog.value = true;
};

// Para debugging - verifica y muestra la membresía correctamente
const getMembershipName = (user) => {
    if (!user || !user.pending_membership_id) return 'No disponible';
    
    if (user.pendingMembership?.nombre) {
        return user.pendingMembership.nombre;
    }
    
    return `ID: ${user.pending_membership_id} (Sin nombre)`;
};

const getMembershipPrice = (user) => {
    if (!user || !user.pendingMembership?.precio) return null;
    return user.pendingMembership.precio;
};

const getMembershipCommission = (user) => {
    if (!user || !user.pendingMembership?.comision_directa) return null;
    return user.pendingMembership.comision_directa;
};

// Función para obtener la información del sponsor
const getSponsorInfo = (user) => {
    if (!user) return null;
    
    if (user.sponsor && user.sponsor.name) {
        return user.sponsor;
    }
    
    return null;
};
</script>

<template>
    <Head title="Membresías Pendientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Membresías Pendientes</h1>
                    <p class="text-gray-500">
                        Gestione las solicitudes de membresías pendientes de aprobación
                    </p>
                </div>
                <Badge variant="outline" class="px-3 py-1 text-base font-semibold">
                    {{ pendingMemberships.length }} pendientes
                </Badge>
            </div>
            
            <div class="bg-white rounded-lg shadow">
                <div class="p-4">
                    <div v-if="pendingMemberships.length === 0" class="py-8 text-center text-gray-500">
                        No hay membresías pendientes de aprobación
                    </div>
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead class="w-[200px]">Usuario</TableHead>
                                    <TableHead>Membresía</TableHead>
                                    <TableHead>Precio</TableHead>
                                    <TableHead>Sponsor</TableHead>
                                    <TableHead>Comprobante</TableHead>
                                    <TableHead>Detalles de Pago</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in pendingMemberships" :key="user.id" class="hover:bg-muted/20">
                                    <!-- Usuario -->
                                    <TableCell class="align-top font-medium">
                                        <div>{{ user.name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                    </TableCell>
                                    
                                    <!-- Membresía -->
                                    <TableCell class="align-top">
                                        <Badge variant="secondary">
                                            {{ getMembershipName(user) }}
                                        </Badge>
                                    </TableCell>
                                    
                                    <!-- Precio -->
                                    <TableCell class="align-top">
                                        <span v-if="getMembershipPrice(user)" class="font-medium">
                                            {{ formatCurrency(getMembershipPrice(user)) }}
                                            <div class="text-xs text-muted-foreground mt-1" v-if="getMembershipCommission(user)">
                                                Comisión: {{ formatCurrency(getMembershipCommission(user)) }}
                                            </div>
                                        </span>
                                        <span v-else class="text-muted-foreground text-sm">
                                            Precio no disponible
                                        </span>
                                    </TableCell>
                                    
                                    <!-- Sponsor -->
                                    <TableCell class="align-top">
                                        <div v-if="getSponsorInfo(user)">
                                            <div>{{ getSponsorInfo(user).name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ getSponsorInfo(user).email }}</div>
                                        </div>
                                        <div v-else class="text-muted-foreground text-sm">
                                            Sin sponsor
                                        </div>
                                    </TableCell>
                                    
                                    <!-- Comprobante -->
                                    <TableCell class="align-top">
                                        <div v-if="getUserDeposit(user.id)?.receipt_image || user.membership_payment_info?.receipt_image" class="flex items-center">
                                            <Badge class="mr-2" variant="outline">
                                                <CreditCard class="h-3 w-3 mr-1" /> 
                                                Disponible
                                            </Badge>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                class="text-blue-600"
                                                @click="showReceipt(getUserDeposit(user.id)?.receipt_image || user.membership_payment_info?.receipt_image || '')"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </div>
                                        <div v-else>
                                            <Badge variant="outline" class="text-amber-600 bg-amber-50">
                                                Sin comprobante
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    
                                    <!-- Detalles de Pago -->
                                    <TableCell class="align-top">
                                        <div v-if="getUserDeposit(user.id) || user.membership_payment_info" class="text-sm">
                                            <div v-if="getUserDeposit(user.id)?.bank_name || user.membership_payment_info?.payment_method">
                                                <span class="font-medium">Banco:</span> {{ getUserDeposit(user.id)?.bank_name || user.membership_payment_info?.payment_method }}
                                            </div>
                                            <div v-if="getUserDeposit(user.id)?.transaction_reference || user.membership_payment_info?.payment_reference" 
                                                class="truncate max-w-40" 
                                                :title="getUserDeposit(user.id)?.transaction_reference || user.membership_payment_info?.payment_reference"
                                            >
                                                <span class="font-medium">Ref:</span> {{ getUserDeposit(user.id)?.transaction_reference || user.membership_payment_info?.payment_reference }}
                                            </div>
                                            <div v-if="getUserDeposit(user.id)?.created_at || user.membership_payment_info?.created_at">
                                                <span class="font-medium">Fecha:</span> {{ 
                                                    formatDate(getUserDeposit(user.id)?.created_at || user.membership_payment_info?.created_at) 
                                                }}
                                            </div>
                                        </div>
                                        <div v-else class="text-muted-foreground text-sm">
                                            No disponible
                                        </div>
                                    </TableCell>
                                    
                                    <!-- Acciones -->
                                    <TableCell class="text-right align-top">
                                        <div class="flex justify-end gap-2 whitespace-nowrap">
                                            <Button 
                                                variant="outline" 
                                                size="sm"
                                                class="text-blue-600 border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                @click="openDetailDialog(user)"
                                            >
                                                <Info class="h-3.5 w-3.5 mr-1" />
                                                Detalles
                                            </Button>
                                            <Button 
                                                variant="outline" 
                                                size="sm"
                                                class="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                                @click="openRejectDialog(user)"
                                                :disabled="isLoading[user.id]"
                                            >
                                                <XCircle class="h-3.5 w-3.5 mr-1" />
                                                Rechazar
                                            </Button>
                                            <Button 
                                                variant="default" 
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700"
                                                @click="openApproveDialog(user)"
                                                :disabled="isLoading[user.id]"
                                            >
                                                <CheckCircle v-if="!isLoading[user.id]" class="h-3.5 w-3.5 mr-1" />
                                                <span v-if="isLoading[user.id]" class="loader mr-1">●</span>
                                                Aprobar
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diálogo para rechazar membresía -->
        <Dialog :open="showRejectDialog" @update:open="showRejectDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rechazar Membresía</DialogTitle>
                    <DialogDescription>
                        Está a punto de rechazar la solicitud de membresía de <span class="font-medium">{{ selectedUser?.name }}</span>.
                        Por favor, indique el motivo del rechazo.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <Label for="rejection_reason" class="mb-2 block">Motivo del rechazo <span class="text-red-500">*</span></Label>
                    <Textarea 
                        id="rejection_reason" 
                        v-model="rejectForm.reason" 
                        placeholder="Indique el motivo por el cual está rechazando esta solicitud de membresía..."
                        class="resize-none h-24"
                        :class="{ 'border-red-500': rejectForm.errors.reason }"
                    />
                    <p v-if="rejectForm.errors.reason" class="text-red-500 text-sm mt-1">
                        {{ rejectForm.errors.reason }}
                    </p>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showRejectDialog = false" :disabled="loadingRejection">
                        Cancelar
                    </Button>
                    <Button 
                        variant="destructive" 
                        @click="rejectMembership" 
                        :disabled="rejectForm.reason === '' || loadingRejection"
                    >
                        <span v-if="loadingRejection" class="loader mr-1.5">●</span>
                        <XCircle v-else class="h-4 w-4 mr-1.5" />
                        Rechazar Membresía
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        
        <!-- Diálogo para aprobar membresía -->
        <Dialog :open="showApproveDialog" @update:open="showApproveDialog = $event">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Confirmar Aprobación</DialogTitle>
                    <DialogDescription>
                        ¿Está seguro que desea aprobar la solicitud de membresía de <span class="font-medium">{{ selectedUser?.name }}</span>?
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-md p-3 text-amber-800 flex gap-2 items-start">
                        <AlertCircle class="h-5 w-5 mt-0.5 flex-shrink-0" />
                        <div class="text-sm">
                            <p>Al aprobar esta membresía:</p>
                            <ul class="list-disc pl-5 mt-1 space-y-1">
                                <li>Se activará la membresía {{ getMembershipName(selectedUser) }} para el usuario</li>
                                <li>Si el usuario tenía una membresía previa, esta será movida al historial</li>
                                <li v-if="getSponsorInfo(selectedUser) && getMembershipCommission(selectedUser)">
                                    Se pagará una comisión de {{ formatCurrency(getMembershipCommission(selectedUser)) }} al sponsor
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Resumen de la solicitud -->
                    <div class="border border-gray-200 rounded-md p-4 mb-4">
                        <h3 class="font-medium text-gray-900 mb-3">Resumen de la solicitud</h3>
                        <div class="grid grid-cols-2 gap-y-2 gap-x-4 text-sm">
                            <div class="text-gray-500">Membresía:</div>
                            <div class="font-medium">
                                {{ getMembershipName(selectedUser) }}
                            </div>
                            
                            <div class="text-gray-500">Precio:</div>
                            <div class="font-medium">
                                <span v-if="getMembershipPrice(selectedUser)">
                                    {{ formatCurrency(getMembershipPrice(selectedUser)) }}
                                </span>
                                <span v-else class="text-gray-500">
                                    No disponible
                                </span>
                            </div>
                            
                            <div class="text-gray-500">Usuario:</div>
                            <div class="font-medium">{{ selectedUser?.name }}</div>
                            
                            <div class="text-gray-500">Email:</div>
                            <div class="font-medium">{{ selectedUser?.email }}</div>
                            
                            <div class="text-gray-500">Sponsor:</div>
                            <div class="font-medium">
                                {{ getSponsorInfo(selectedUser) ? getSponsorInfo(selectedUser).name : 'No tiene' }}
                            </div>
                            
                            <div class="text-gray-500">Comprobante:</div>
                            <div class="font-medium">
                                <span v-if="selectedUser && (getUserDeposit(selectedUser.id)?.receipt_image || selectedUser.membership_payment_info?.receipt_image)">
                                    <Button 
                                        variant="link" 
                                        size="sm" 
                                        class="p-0 h-auto text-blue-600 underline"
                                        @click="showReceipt(getUserDeposit(selectedUser.id)?.receipt_image || selectedUser.membership_payment_info?.receipt_image || '')"
                                    >
                                        Ver comprobante
                                    </Button>
                                </span>
                                <span v-else>No disponible</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showApproveDialog = false" :disabled="loadingApproval">
                        Cancelar
                    </Button>
                    <Button 
                        variant="default" 
                        class="bg-green-600 hover:bg-green-700"
                        @click="approveMembership" 
                        :disabled="loadingApproval"
                    >
                        <span v-if="loadingApproval" class="loader mr-1.5">●</span>
                        <CheckCircle v-else class="h-4 w-4 mr-1.5" />
                        Confirmar Aprobación
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        
        <!-- Diálogo para ver detalles de la membresía y el usuario -->
        <Dialog :open="showDetailDialog" @update:open="showDetailDialog = $event">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Detalles de la Solicitud</DialogTitle>
                    <DialogDescription>
                        Información detallada de la solicitud de membresía y el usuario
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 space-y-6">
                    <!-- Información del usuario -->
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <User class="h-5 w-5 text-blue-600" />
                            Información del Usuario
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Nombre</div>
                                <div class="font-medium">{{ selectedUser?.name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Email</div>
                                <div class="font-medium">{{ selectedUser?.email }}</div>
                            </div>
                            <div v-if="getSponsorInfo(selectedUser)">
                                <div class="text-sm text-gray-500 mb-1">Sponsor</div>
                                <div class="font-medium">{{ getSponsorInfo(selectedUser).name }}</div>
                                <div class="text-sm text-gray-500">{{ getSponsorInfo(selectedUser).email }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de la membresía -->
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <FileText class="h-5 w-5 text-blue-600" />
                            Detalles de Membresía
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Membresía</div>
                                <div>
                                    <Badge variant="secondary" class="text-sm">
                                        {{ getMembershipName(selectedUser) }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-if="getMembershipPrice(selectedUser)">
                                <div class="text-sm text-gray-500 mb-1">Precio</div>
                                <div class="font-medium">
                                    {{ formatCurrency(getMembershipPrice(selectedUser)) }}
                                </div>
                            </div>
                            <div v-if="getMembershipCommission(selectedUser)">
                                <div class="text-sm text-gray-500 mb-1">Comisión para Sponsor</div>
                                <div class="font-medium">
                                    {{ formatCurrency(getMembershipCommission(selectedUser)) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del pago -->
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <DollarSign class="h-5 w-5 text-blue-600" />
                            Detalles de Pago
                        </h3>
                        
                        <div v-if="selectedUser && (getUserDeposit(selectedUser.id) || selectedUser.membership_payment_info)">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="getUserDeposit(selectedUser.id)?.bank_name || selectedUser.membership_payment_info?.payment_method">
                                    <div class="text-sm text-gray-500 mb-1">Banco/Método</div>
                                    <div class="font-medium">{{ getUserDeposit(selectedUser.id)?.bank_name || selectedUser.membership_payment_info?.payment_method }}</div>
                                </div>
                                <div v-if="getUserDeposit(selectedUser.id)?.transaction_reference || selectedUser.membership_payment_info?.payment_reference">
                                    <div class="text-sm text-gray-500 mb-1">Referencia</div>
                                    <div class="font-medium">{{ getUserDeposit(selectedUser.id)?.transaction_reference || selectedUser.membership_payment_info?.payment_reference }}</div>
                                </div>
                                <div v-if="getUserDeposit(selectedUser.id)?.created_at || selectedUser.membership_payment_info?.created_at">
                                    <div class="text-sm text-gray-500 mb-1">Fecha</div>
                                    <div class="font-medium">{{ formatDate(getUserDeposit(selectedUser.id)?.created_at || selectedUser.membership_payment_info?.created_at) }}</div>
                                </div>
                                <div v-if="getUserDeposit(selectedUser.id)?.receipt_image || selectedUser.membership_payment_info?.receipt_image">
                                    <div class="text-sm text-gray-500 mb-1">Comprobante</div>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        class="mt-1"
                                        @click="showReceipt(getUserDeposit(selectedUser.id)?.receipt_image || selectedUser.membership_payment_info?.receipt_image || '')"
                                    >
                                        <Eye class="h-4 w-4 mr-1.5" />
                                        Ver Comprobante
                                    </Button>
                                </div>
                                <div v-if="getUserDeposit(selectedUser.id)?.notes || selectedUser.membership_payment_info?.notes" class="col-span-2">
                                    <div class="text-sm text-gray-500 mb-1">Notas</div>
                                    <div class="text-sm bg-gray-50 p-2 rounded">
                                        {{ getUserDeposit(selectedUser.id)?.notes || selectedUser.membership_payment_info?.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-4 text-gray-500">
                            No hay información de pago disponible
                        </div>
                    </div>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showDetailDialog = false">
                        Cerrar
                    </Button>
                    <div class="flex gap-2">
                        <Button 
                            variant="outline" 
                            class="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                            @click="() => { showDetailDialog = false; openRejectDialog(selectedUser); }"
                            :disabled="!selectedUser || isLoading[selectedUser.id]"
                        >
                            <XCircle class="h-4 w-4 mr-1.5" />
                            Rechazar
                        </Button>
                        <Button 
                            variant="default" 
                            class="bg-green-600 hover:bg-green-700"
                            @click="() => { showDetailDialog = false; openApproveDialog(selectedUser); }"
                            :disabled="!selectedUser || isLoading[selectedUser.id]"
                        >
                            <CheckCircle class="h-4 w-4 mr-1.5" />
                            Aprobar
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        
        <!-- Diálogo para mostrar comprobante -->
        <Dialog :open="showReceiptDialog" @update:open="showReceiptDialog = $event">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Comprobante de Pago</DialogTitle>
                    <DialogDescription>
                        Visualización del comprobante de pago subido
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 flex justify-center">
                    <img 
                        v-if="selectedReceiptUrl" 
                        :src="`/storage/${selectedReceiptUrl}`" 
                        alt="Comprobante de pago" 
                        class="max-w-full max-h-[60vh] object-contain shadow-md rounded"
                    />
                    <div v-else class="text-center text-gray-500 py-8">
                        No se pudo cargar la imagen
                    </div>
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showReceiptDialog = false">
                        Cerrar
                    </Button>
                    <a 
                        v-if="selectedReceiptUrl" 
                        :href="`/storage/${selectedReceiptUrl}`" 
                        target="_blank" 
                        rel="noopener noreferrer"
                    >
                        <Button variant="default">
                            Ver en nueva ventana
                        </Button>
                    </a>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.loader {
    display: inline-block;
    animation: pulse 1.4s infinite;
}

@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}
</style> 