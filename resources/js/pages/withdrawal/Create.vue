<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface Props {
    hasWallet: boolean;
    wallet?: {
        address: string;
        type: string;
    };
}

const props = defineProps<Props>();

// Formulario para solicitud de retiro
const form = useForm({
    amount: '',
    wallet_address: props.wallet?.address || '',
    verification_token: '',
    step: 'request_token' // 'request_token' o 'verify_token'
});

// Estado para el token de verificación
const tokenSent = ref(false);
const verificationError = ref('');
const showVerificationModal = ref(false);

// Solicitar token de verificación
const requestVerificationToken = () => {
    // Validar que los campos necesarios estén completos
    if (!form.amount || !form.wallet_address) {
        return;
    }

    form.post(route('api.withdrawal.request-token'), {
        preserveScroll: true,
        onSuccess: () => {
            tokenSent.value = true;
            form.step = 'verify_token';
            showVerificationModal.value = true;
        },
        onError: (errors) => {
            if (errors.message) {
                verificationError.value = errors.message;
            } else {
                verificationError.value = 'Error al solicitar el código de verificación';
            }
        }
    });
};

// Reenviar código
const resendVerificationToken = () => {
    form.post(route('api.withdrawal.request-token'), {
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

// Manejar el envío del formulario
const submitForm = () => {
    form.post(route('withdrawal.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showVerificationModal.value = false;
        },
        onError: () => {
            showVerificationModal.value = true;
        }
    });
};

// Cerrar el modal de verificación
const closeVerificationModal = () => {
    showVerificationModal.value = false;
    // Si el usuario cierra el modal sin verificar, volvemos al paso inicial
    if (!form.recentlySuccessful) {
        form.step = 'request_token';
        tokenSent.value = false;
        form.verification_token = '';
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Solicitar Retiro" />
        
        <div class="container mx-auto py-8">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-2xl font-bold mb-6">Solicitar Retiro</h1>
                
                <Card v-if="!hasWallet" class="mb-6">
                    <CardHeader>
                        <CardTitle>Billetera no configurada</CardTitle>
                        <CardDescription>
                            Necesita configurar su billetera antes de poder realizar retiros.
                        </CardDescription>
                    </CardHeader>
                    <CardFooter>
                        <Button :href="route('settings.wallet')" as="a">Configurar Billetera</Button>
                    </CardFooter>
                </Card>
                
                <Card v-else>
                    <CardHeader>
                        <CardTitle>Formulario de Retiro</CardTitle>
                        <CardDescription>
                            Complete los datos para solicitar un retiro de fondos a su billetera.
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent>
                        <form @submit.prevent="requestVerificationToken" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="amount">Monto a retirar</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                    <Input 
                                        id="amount" 
                                        v-model="form.amount" 
                                        type="number" 
                                        placeholder="0.00" 
                                        class="pl-8"
                                        :class="{ 'border-red-500': form.errors.amount }"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.amount" class="text-sm text-red-500">{{ form.errors.amount }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="wallet_address">Dirección de billetera</Label>
                                <Input 
                                    id="wallet_address" 
                                    v-model="form.wallet_address" 
                                    type="text" 
                                    placeholder="Dirección de billetera" 
                                    :class="{ 'border-red-500': form.errors.wallet_address }"
                                    :disabled="true"
                                    required
                                />
                                <p class="text-sm text-gray-500">
                                    Esta es la dirección de billetera configurada en su perfil. 
                                    <a :href="route('settings.wallet')" class="text-blue-500 hover:underline">Modificar</a>
                                </p>
                                <p v-if="form.errors.wallet_address" class="text-sm text-red-500">{{ form.errors.wallet_address }}</p>
                            </div>
                            
                            <Alert v-if="verificationError" class="bg-red-50 border-red-200">
                                <AlertCircle class="h-4 w-4 text-red-500" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>
                                    {{ verificationError }}
                                </AlertDescription>
                            </Alert>

                            <div class="flex items-center gap-4">
                                <Button 
                                    type="submit" 
                                    :disabled="form.processing || !form.amount || !form.wallet_address"
                                >
                                    <template v-if="form.processing">
                                        Procesando...
                                    </template>
                                    <template v-else>
                                        Solicitar Retiro
                                    </template>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                    
                    <CardFooter class="flex justify-between">
                        <Button variant="outline" :href="route('withdrawal.history')" as="a">Cancelar</Button>
                    </CardFooter>
                </Card>
            </div>
        </div>

        <!-- Modal de Verificación -->
        <Dialog :open="showVerificationModal" @update:open="closeVerificationModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Verificación de Retiro</DialogTitle>
                    <DialogDescription>
                        Ingrese el código de verificación enviado a su correo electrónico para confirmar el retiro.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="verification_token_modal">Código de verificación</Label>
                        <Input 
                            id="verification_token_modal" 
                            v-model="form.verification_token" 
                            type="text" 
                            placeholder="Ingrese el código recibido por correo" 
                            :class="{ 'border-red-500': form.errors.verification_token }"
                            required
                        />
                        <p v-if="form.errors.verification_token" class="text-sm text-red-500">{{ form.errors.verification_token }}</p>
                        <p class="text-sm text-gray-500">
                            Se ha enviado un código de verificación a su correo electrónico. 
                            <Button variant="link" class="p-0 h-auto" @click="resendVerificationToken" type="button">Reenviar código</Button>
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeVerificationModal">
                        Cancelar
                    </Button>
                    <Button type="button" @click="submitForm" :disabled="form.processing || !form.verification_token">
                        <template v-if="form.processing">
                            Procesando...
                        </template>
                        <template v-else>
                            Verificar y Confirmar Retiro
                        </template>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>