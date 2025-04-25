<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { DollarSign, Wallet, Users, AlertTriangle, CheckCircle, XCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface Props {
    hasWallet: boolean;
    wallet: {
        id: number;
        address: string;
        type: string;
    } | null;
    hasPendingWithdrawal: boolean;
    withdrawalRules: {
        capital: {
            available: boolean;
            days: string;
            balance: number;
        };
        earnings: {
            available: boolean;
            days: string;
            balance: number;
        };
        network: {
            available: boolean;
            days: string;
            balance: number;
        };
    };
    withdrawalLimits: {
        min: number;
        max: number;
    };
    latestTransactions: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Retiros',
        href: route('withdrawal.create'),
    },
];

// Para la selección de tipo de saldo
const selectedBalanceType = ref<'capital' | 'earnings' | 'network'>('earnings');

// Comprobar si se puede retirar del tipo de saldo seleccionado
const canWithdrawSelected = computed(() => {
    if (selectedBalanceType.value === 'capital') {
        return props.withdrawalRules.capital.available;
    } else if (selectedBalanceType.value === 'earnings') {
        return props.withdrawalRules.earnings.available;
    } else {
        return props.withdrawalRules.network.available;
    }
});

// Obtener el saldo actual del tipo seleccionado
const currentBalance = computed(() => {
    if (selectedBalanceType.value === 'capital') {
        return props.withdrawalRules.capital.balance;
    } else if (selectedBalanceType.value === 'earnings') {
        return props.withdrawalRules.earnings.balance;
    } else {
        return props.withdrawalRules.network.balance;
    }
});

// Para alternar entre los pasos del formulario
const isVerifyStep = ref(false);

// Formulario para solicitar token
const tokenForm = useForm({
    amount: '',
    balance_type: selectedBalanceType.value,
    message: '',
});

// Formulario para finalizar retiro
const withdrawalForm = useForm({
    amount: '',
    verification_token: '',
    balance_type: selectedBalanceType.value,
});

// Solicitar token de verificación
const requestVerificationToken = () => {
    tokenForm.balance_type = selectedBalanceType.value;
    tokenForm.post(route('api.withdrawal.request-token'), {
        preserveScroll: true,
        onSuccess: () => {
            isVerifyStep.value = true;
            withdrawalForm.amount = tokenForm.amount;
            withdrawalForm.balance_type = selectedBalanceType.value;
        },
    });
};

// Finalizar solicitud de retiro
const submitWithdrawal = () => {
    withdrawalForm.balance_type = selectedBalanceType.value;
    withdrawalForm.post(route('withdrawal.store'), {
        preserveScroll: true,
    });
};

// Cambiar el tipo de saldo seleccionado
const changeBalanceType = (type: 'capital' | 'earnings' | 'network') => {
    if (props.withdrawalRules[type].available) {
        selectedBalanceType.value = type;
    }
};

const filteredTransactions = computed(() => {
    return props.latestTransactions.filter(transaction => 
        transaction.transaction_type !== 'withdrawal_refund'
    );
});
</script>

<template>
    <Head title="Solicitud de Retiro" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <!-- Mensaje de error global -->
            <Alert v-if="tokenForm.errors.message" class="mb-6" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>
                    {{ tokenForm.errors.message }}
                </AlertDescription>
            </Alert>
            
            <!-- Alerta si no tiene billetera configurada -->
            <Alert v-if="!hasWallet" class="mb-6" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertTitle>Atención</AlertTitle>
                <AlertDescription>
                    Debe configurar su billetera antes de realizar retiros.
                    <a :href="route('profile.edit')" class="font-medium underline">Configurar billetera</a>
                </AlertDescription>
            </Alert>
            
            <!-- Alerta si ya tiene un retiro pendiente -->
            <Alert v-if="hasPendingWithdrawal" class="mb-6" variant="warning">
                <AlertTriangle class="h-4 w-4" />
                <AlertTitle>Retiro en proceso</AlertTitle>
                <AlertDescription>
                    Ya tiene una solicitud de retiro pendiente. Debe esperar a que se procese antes de realizar otra solicitud.
                    <a :href="route('withdrawal.history')" class="font-medium underline">Ver historial de retiros</a>
                </AlertDescription>
            </Alert>
            
            <!-- Información de billetera -->
            <Card v-if="hasWallet && !hasPendingWithdrawal" class="mb-6">
                <CardHeader>
                    <CardTitle>Solicitud de Retiro</CardTitle>
                    <CardDescription>Complete el formulario para solicitar un retiro de fondos.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="mb-6">
                        <Label>Su billetera de destino</Label>
                        <div class="mt-2 p-3 bg-gray-100 rounded-md">
                            <div class="font-medium">{{ wallet?.type.toUpperCase() }}</div>
                            <div class="text-sm text-gray-600 break-all">{{ wallet?.address }}</div>
                        </div>
                    </div>
                    
                    <Separator class="my-6" />
                    
                    <!-- Selección de tipo de saldo -->
                    <div class="mb-6">
                        <Label>Seleccione el tipo de saldo</Label>
                        <div class="mt-3 grid gap-4">
                            <!-- Capital -->
                            <div 
                                class="flex p-3 border rounded-md cursor-pointer" 
                                :class="[
                                    selectedBalanceType === 'capital' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200',
                                    !props.withdrawalRules.capital.available ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                @click="changeBalanceType('capital')"
                            >
                                <div class="flex-shrink-0 mr-3">
                                    <DollarSign class="h-5 w-5 text-green-600" />
                                </div>
                                <div class="flex-grow">
                                    <div class="font-medium text-gray-900">Capital</div>
                                    <div class="text-sm text-gray-600">
                                        <span v-if="props.withdrawalRules.capital.available" class="text-green-600 flex items-center text-xs">
                                            <CheckCircle class="h-3 w-3 mr-1" />
                                            Disponible para retiro hoy
                                        </span>
                                        <span v-else class="text-red-600 flex items-center text-xs">
                                            <XCircle class="h-3 w-3 mr-1" />
                                            Solo disponible: {{ props.withdrawalRules.capital.days }}
                                        </span>
                                    </div>
                                    <div class="text-sm font-semibold mt-1">
                                        Saldo: {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.withdrawalRules.capital.balance) }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ganancias -->
                            <div 
                                class="flex p-3 border rounded-md cursor-pointer" 
                                :class="[
                                    selectedBalanceType === 'earnings' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200',
                                    !props.withdrawalRules.earnings.available ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                @click="changeBalanceType('earnings')"
                            >
                                <div class="flex-shrink-0 mr-3">
                                    <Wallet class="h-5 w-5 text-blue-600" />
                                </div>
                                <div class="flex-grow">
                                    <div class="font-medium text-gray-900">Ganancias</div>
                                    <div class="text-sm text-gray-600">
                                        <span v-if="props.withdrawalRules.earnings.available" class="text-green-600 flex items-center text-xs">
                                            <CheckCircle class="h-3 w-3 mr-1" />
                                            Disponible para retiro hoy
                                        </span>
                                        <span v-else class="text-red-600 flex items-center text-xs">
                                            <XCircle class="h-3 w-3 mr-1" />
                                            Solo disponible: {{ props.withdrawalRules.earnings.days }}
                                        </span>
                                    </div>
                                    <div class="text-sm font-semibold mt-1">
                                        Saldo: {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.withdrawalRules.earnings.balance) }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Comisiones Red -->
                            <div 
                                class="flex p-3 border rounded-md cursor-pointer" 
                                :class="[
                                    selectedBalanceType === 'network' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200',
                                    !props.withdrawalRules.network.available ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                @click="changeBalanceType('network')"
                            >
                                <div class="flex-shrink-0 mr-3">
                                    <Users class="h-5 w-5 text-purple-600" />
                                </div>
                                <div class="flex-grow">
                                    <div class="font-medium text-gray-900">Comisiones de Red</div>
                                    <div class="text-sm text-gray-600">
                                        <span v-if="props.withdrawalRules.network.available" class="text-green-600 flex items-center text-xs">
                                            <CheckCircle class="h-3 w-3 mr-1" />
                                            Disponible para retiro hoy
                                        </span>
                                        <span v-else class="text-red-600 flex items-center text-xs">
                                            <XCircle class="h-3 w-3 mr-1" />
                                            Solo disponible: {{ props.withdrawalRules.network.days }}
                                        </span>
                                    </div>
                                    <div class="text-sm font-semibold mt-1">
                                        Saldo: {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.withdrawalRules.network.balance) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <Separator class="my-6" />
                    
                    <!-- Pasos de retiro -->
                    <div v-if="!isVerifyStep">
                        <!-- Paso 1: Solicitar código -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Solicitar Código de Verificación</CardTitle>
                                <CardDescription>Ingrese el monto a retirar y solicite un código de verificación</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div>
                                        <Label for="amount">Monto a retirar ($)</Label>
                                        <Input
                                            id="amount"
                                            v-model="tokenForm.amount"
                                            type="number"
                                            step="0.01"
                                            min="1"
                                            :max="currentBalance"
                                            placeholder="Ingrese el monto"
                                            :disabled="!canWithdrawSelected || tokenForm.processing"
                                            class="mt-1"
                                        />
                                        <div class="text-sm text-gray-500 mt-1">
                                            Monto mínimo: ${{ withdrawalLimits.min }} - Monto máximo: ${{ withdrawalLimits.max }}
                                        </div>
                                        <div v-if="tokenForm.errors.amount" class="text-sm text-red-500 mt-1">
                                            {{ tokenForm.errors.amount }}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button
                                    @click="requestVerificationToken"
                                    :disabled="!canWithdrawSelected || !tokenForm.amount || tokenForm.processing"
                                >
                                    Solicitar Código de Verificación
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                    
                    <div v-else>
                        <!-- Paso 2: Verificar código -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Verificar Código</CardTitle>
                                <CardDescription>
                                    Se ha enviado un código de verificación a su correo electrónico.
                                    Ingréselo a continuación para completar la solicitud de retiro.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div>
                                        <Label for="verification-code">Código de Verificación</Label>
                                        <Input
                                            id="verification-code"
                                            v-model="withdrawalForm.verification_token"
                                            placeholder="Ingrese el código de 6 dígitos"
                                            :disabled="withdrawalForm.processing"
                                            class="mt-1"
                                        />
                                        <div v-if="withdrawalForm.errors.verification_token" class="text-sm text-red-500 mt-1">
                                            {{ withdrawalForm.errors.verification_token }}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex justify-between">
                                <Button 
                                    variant="outline" 
                                    @click="isVerifyStep = false"
                                    :disabled="withdrawalForm.processing"
                                >
                                    Volver
                                </Button>
                                <Button
                                    @click="submitWithdrawal"
                                    :disabled="!withdrawalForm.verification_token || withdrawalForm.processing"
                                >
                                    Confirmar Retiro de ${{ withdrawalForm.amount }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>