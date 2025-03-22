<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Select from '@/components/ui/select/Select.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, Info } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface Props {
    wallet?: {
        address: string;
        type: string;
    };
    walletHistory?: Array<{
        id: number;
        address: string;
        type: string;
        created_at: string;
        updated_at: string;
    }>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings',
    },
    {
        title: 'Billetera',
        href: '/settings/wallet',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

// Lista de tipos de billetera
const walletTypes = [
    { id: 'trc20', name: 'TRC20' },
    { id: 'bep20', name: 'BEP20' },
    { id: 'opbnb', name: 'OPBNB' }
];

// Estado para verificación
const verificationSent = ref(false);
const verificationError = ref('');
const showVerificationModal = ref(false);

const form = useForm({
    wallet_address: '',
    wallet_type: '',
    verification_code: '',
    step: 'request_code' // 'request_code' o 'verify_code'
});

// Referencia para el componente SelectTrigger
const selectTriggerRef = ref(null);

// Manejar el botón principal
const handleMainAction = () => {
    if (form.step === 'request_code') {
        requestVerificationCode();
    } else {
        submitWallet();
    }
};

// Solicitar código de verificación
const requestVerificationCode = () => {
    // Validar que los campos necesarios estén completos
    if (!form.wallet_address || !form.wallet_type) {
        return;
    }

    form.post(route('api.wallet.request-verification'), {
        preserveScroll: true,
        onSuccess: () => {
            verificationSent.value = true;
            form.step = 'verify_code';
            showVerificationModal.value = true;
        },
        onError: (errors) => {
            if (errors.message) {
                verificationError.value = errors.message;
            } else {
                verificationError.value = 'Error al enviar el código de verificación';
            }
        }
    });
};

// Reenviar código
const resendVerificationCode = () => {
    form.post(route('api.wallet.request-verification'), {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.message) {
                verificationError.value = errors.message;
            } else {
                verificationError.value = 'Error al reenviar el código de verificación';
            }
        }
    });
};

// Enviar formulario final
const submitWallet = () => {
    form.post(route('settings.wallet.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Resetear estado después de guardar
            verificationSent.value = false;
            verificationError.value = '';
            form.step = 'request_code';
            form.verification_code = '';
            showVerificationModal.value = false;
        },
        onError: () => {
            // Mantener el modal abierto si hay errores
            showVerificationModal.value = true;
        }
    });
};

// Cerrar el modal de verificación
const closeVerificationModal = () => {
    showVerificationModal.value = false;
    // Si el usuario cierra el modal sin verificar, volvemos al paso inicial
    if (!form.recentlySuccessful) {
        form.step = 'request_code';
        verificationSent.value = false;
        form.verification_code = '';
    }
};

// Formatear el tipo de billetera para mostrar
const getWalletTypeName = (typeId: string) => {
    const walletType = walletTypes.find(type => type.id === typeId);
    return walletType ? walletType.name : typeId;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Configuración de Billetera" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall 
                    title="Configuración de Billetera" 
                    description="Configure su billetera para poder realizar retiros de fondos" 
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Información de Billetera</CardTitle>
                        <CardDescription>
                            Esta información será utilizada para procesar sus retiros.
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent>
                        <form @submit.prevent="handleMainAction" class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="wallet_type">Tipo de Red</Label>
                                <Select 
                                    v-model="form.wallet_type" 
                                    required
                                >
                                    <SelectTrigger 
                                        :class="{ 'border-red-500': form.errors.wallet_type }"
                                    >
                                        <SelectValue placeholder="Seleccione tipo de red">
                                            {{ getWalletTypeName(form.wallet_type) }}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in walletTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError class="mt-1" :message="form.errors.wallet_type" />
                            </div>

                            <!-- Billetera actual (si existe) -->
                            <div v-if="wallet" class="grid gap-2 mb-4">
                                <Label for="current_wallet">Billetera Actual</Label>
                                <Input
                                    id="current_wallet"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="wallet.address"                                    
                                    v-model="wallet.address"
                                    disabled
                                />
                                <p class="text-sm text-gray-500">
                                    <Info class="inline h-4 w-4 mr-1" />
                                    Esta es su billetera actual registrada como {{ getWalletTypeName(wallet.type) }}.
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="wallet_address">Nueva Dirección de Billetera</Label>
                                <Input
                                    id="wallet_address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.wallet_address"
                                    required
                                    placeholder="Ingrese la dirección de su nueva billetera"
                                />
                                <p class="text-sm text-gray-500">
                                    <Info class="inline h-4 w-4 mr-1" />
                                    Asegúrese de ingresar la dirección correcta. Los fondos enviados a una dirección incorrecta no podrán ser recuperados.
                                </p>
                                <InputError class="mt-1" :message="form.errors.wallet_address" />
                            </div>

                            <Alert v-if="verificationError" class="bg-red-50 border-red-200">
                                <AlertCircle class="h-4 w-4 text-red-500" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>
                                    {{ verificationError }}
                                </AlertDescription>
                            </Alert>

                            <div class="flex items-center gap-4">
                                <!-- Botón principal adaptativo según el estado -->
                                <Button 
                                    type="submit" 
                                    :disabled="form.processing || (!form.wallet_address || !form.wallet_type)"
                                >
                                    <template v-if="form.processing">
                                        Procesando...
                                    </template>
                                    <template v-else>
                                        Registrar Billetera
                                    </template>
                                </Button>

                                <TransitionRoot
                                    :show="form.recentlySuccessful"
                                    enter="transition ease-in-out"
                                    enter-from="opacity-0"
                                    leave="transition ease-in-out"
                                    leave-to="opacity-0"
                                >
                                    <p class="text-sm text-green-600">Guardado correctamente.</p>
                                </TransitionRoot>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Historial de Billeteras -->
                <Card v-if="walletHistory && walletHistory.length > 0">
                    <CardHeader>
                        <CardTitle>Historial de Billeteras</CardTitle>
                        <CardDescription>
                            Registro histórico de sus billeteras configuradas
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs  uppercase ">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Red</th>
                                        <th scope="col" class="px-6 py-3">Dirección</th>
                                        <th scope="col" class="px-6 py-3">Fecha de registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="wallet in walletHistory" :key="wallet.id" class="border-b">
                                        <td class="px-6 py-4">{{ getWalletTypeName(wallet.type) }}</td>
                                        <td class="px-6 py-4">{{ wallet.address }}</td>
                                        <td class="px-6 py-4">{{ new Date(wallet.created_at).toLocaleString() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>

        <!-- Modal de Verificación -->
        <Dialog :open="showVerificationModal" @update:open="closeVerificationModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Verificación de Billetera</DialogTitle>
                    <DialogDescription>
                        Ingrese el código de verificación enviado a su correo electrónico para confirmar la billetera.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="verification_code_modal">Código de verificación</Label>
                        <Input 
                            id="verification_code_modal" 
                            v-model="form.verification_code" 
                            type="text" 
                            placeholder="Ingrese el código recibido por correo" 
                            :class="{ 'border-red-500': form.errors.verification_code }"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.verification_code" />
                        <p class="text-sm text-gray-500">
                            Se ha enviado un código de verificación a su correo electrónico. 
                            <Button variant="link" class="p-0 h-auto" @click="resendVerificationCode" type="button">Reenviar código</Button>
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeVerificationModal">
                        Cancelar
                    </Button>
                    <Button type="button" @click="submitWallet" :disabled="form.processing || !form.verification_code">
                        <template v-if="form.processing">
                            Procesando...
                        </template>
                        <template v-else>
                            Verificar y Guardar
                        </template>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>