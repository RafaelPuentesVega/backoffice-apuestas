<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/card/Card.vue';
import CardHeader from '@/Components/ui/card/CardHeader.vue';
import CardTitle from '@/Components/ui/card/CardTitle.vue';
import CardContent from '@/Components/ui/card/CardContent.vue';
import CardFooter from '@/Components/ui/card/CardFooter.vue';
import Button from '@/Components/ui/button/Button.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import Label from '@/Components/ui/label/Label.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface User {
    id: number;
    name: string;
    email: string;
    capital_balance: number;
    earnings_balance: number;
    network_balance: number;
    membership?: {
        id: number;
        nombre: string;
        precio: number;
    } | null;
}

interface Deposit {
    id: number;
    user_id: number;
    user: User;
    amount: number;
    balance_type: string;
    transaction_reference: string;
    bank_name: string;
    receipt_image: string;
    status: 'pendiente' | 'aprobado' | 'rechazado';
    notes: string | null;
    created_at: string;
    processed_at: string | null;
}

interface Props {
    pendingDeposits: Deposit[];
    recentDeposits: Deposit[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Administración', href: '/admin' },
    { label: 'Depósitos', href: route('admin.deposits') }
];

// Estado para los modales
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const selectedDeposit = ref<Deposit | null>(null);
const showDetailsDialog = ref(false);
const detailsDeposit = ref<Deposit | null>(null);

// Formulario para rechazo
const rejectForm = useForm({
    rejection_reason: '',
});

// Formulario para aprobación
const approveForm = useForm({});
const isApproving = ref(false);

function formatDate(dateString: string): string {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatBalanceType(type: string): string {
    switch (type) {
        case 'capital': return 'Capital';
        case 'earnings': return 'Ganancias';
        case 'network': return 'Comisiones de Red';
        default: return type;
    }
}

function getStatusColor(status: string): string {
    switch (status) {
        case 'pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'aprobado': return 'bg-green-100 text-green-800';
        case 'rechazado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusText(status: string): string {
    switch (status) {
        case 'pendiente': return 'Pendiente';
        case 'aprobado': return 'Aprobado';
        case 'rechazado': return 'Rechazado';
        default: return status;
    }
}

function openApproveDialog(deposit: Deposit) {
    selectedDeposit.value = deposit;
    showApproveDialog.value = true;
}

function openRejectDialog(deposit: Deposit) {
    selectedDeposit.value = deposit;
    rejectForm.rejection_reason = '';
    showRejectDialog.value = true;
}

function openDetailsDialog(deposit: Deposit) {
    detailsDeposit.value = deposit;
    showDetailsDialog.value = true;
}

function approveDeposit() {
    if (selectedDeposit.value) {
        isApproving.value = true;
        approveForm.post(route('admin.deposits.approve', { deposit: selectedDeposit.value.id }), {
            onSuccess: () => {
                showApproveDialog.value = false;
                isApproving.value = false;
            },
            onError: () => {
                isApproving.value = false;
            }
        });
    }
}

function rejectDeposit() {
    if (selectedDeposit.value) {
        rejectForm.post(route('admin.deposits.reject', { deposit: selectedDeposit.value.id }), {
            onSuccess: () => {
                showRejectDialog.value = false;
            }
        });
    }
}
</script>

<template>
    <Head title="Administración de Depósitos" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem v-for="(item, index) in breadcrumbs" :key="index">
                                <a v-if="index < breadcrumbs.length - 1" :href="item.href" class="text-gray-500 hover:text-gray-700">
                                    {{ item.label }}
                                </a>
                                <span v-else class="font-medium text-gray-900">{{ item.label }}</span>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>

                <h1 class="text-2xl font-semibold text-gray-900 mb-6">Administración de Depósitos</h1>

                <!-- Depósitos pendientes -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Depósitos Pendientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.pendingDeposits.length === 0" class="text-center py-8 text-gray-500">
                            No hay depósitos pendientes.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banco</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="deposit in props.pendingDeposits" :key="deposit.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(deposit.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ deposit.user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ deposit.user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="deposit.user.membership" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ deposit.user.membership.nombre }}
                                            </span>
                                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Sin membresía
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(deposit.amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ deposit.bank_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex space-x-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-gray-600" 
                                                @click="openDetailsDialog(deposit)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Button>
                                            <a :href="`/storage/${deposit.receipt_image}`" target="_blank" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </a>
                                            <Button
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700 text-white"
                                                @click="openApproveDialog(deposit)"
                                            >
                                                Aprobar
                                            </Button>
                                            <Button
                                                size="sm"
                                                class="bg-red-600 hover:bg-red-700 text-white"
                                                @click="openRejectDialog(deposit)"
                                            >
                                                Rechazar
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Depósitos recientes -->
                <Card>
                    <CardHeader>
                        <CardTitle>Depósitos Recientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.recentDeposits.length === 0" class="text-center py-8 text-gray-500">
                            No hay depósitos recientes.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="deposit in props.recentDeposits" :key="deposit.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(deposit.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ deposit.user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ deposit.user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="deposit.user.membership" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ deposit.user.membership.nombre }}
                                            </span>
                                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Sin membresía
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(deposit.amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(deposit.status)}`">
                                                {{ getStatusText(deposit.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex space-x-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-gray-600" 
                                                @click="openDetailsDialog(deposit)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Button>
                                            <a :href="`/storage/${deposit.receipt_image}`" target="_blank" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Modal de confirmación para aprobar -->
        <Dialog :open="showApproveDialog" @update:open="showApproveDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar Aprobación</DialogTitle>
                    <DialogDescription>
                        ¿Está seguro que desea aprobar este depósito?
                    </DialogDescription>
                </DialogHeader>
                <div v-if="selectedDeposit" class="py-4">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Al aprobar este depósito, se aumentará el saldo de capital del usuario en {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(selectedDeposit.amount) }}.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Información del Usuario</h3>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Nombre</p>
                                    <p class="text-sm text-gray-900">{{ selectedDeposit.user.name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Email</p>
                                    <p class="text-sm text-gray-900">{{ selectedDeposit.user.email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Membresía</p>
                                    <p class="text-sm">
                                        <span v-if="selectedDeposit.user.membership" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ selectedDeposit.user.membership.nombre }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Sin membresía
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <p class="text-xs font-medium text-gray-500 mb-1">Saldos actuales</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-white p-2 rounded border">
                                        <p class="text-xs text-gray-500">Capital</p>
                                        <p class="text-sm font-medium">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(selectedDeposit.user.capital_balance || 0) }}</p>
                                    </div>
                                    <div class="bg-white p-2 rounded border">
                                        <p class="text-xs text-gray-500">Ganancias</p>
                                        <p class="text-sm font-medium">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(selectedDeposit.user.earnings_balance || 0) }}</p>
                                    </div>
                                    <div class="bg-white p-2 rounded border">
                                        <p class="text-xs text-gray-500">Red</p>
                                        <p class="text-sm font-medium">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(selectedDeposit.user.network_balance || 0) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Información del Depósito</h3>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Monto</p>
                                    <p class="text-sm font-medium text-green-600">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(selectedDeposit.amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Tipo</p>
                                    <p class="text-sm">{{ formatBalanceType(selectedDeposit.balance_type) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Banco</p>
                                    <p class="text-sm">{{ selectedDeposit.bank_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Referencia</p>
                                    <p class="text-sm">{{ selectedDeposit.transaction_reference }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-2 text-right">
                                <a :href="`/storage/${selectedDeposit.receipt_image}`" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Ver comprobante
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter class="flex justify-end space-x-2">
                    <Button variant="outline" @click="showApproveDialog = false" :disabled="isApproving">Cancelar</Button>
                    <Button 
                        class="bg-green-600 hover:bg-green-700 text-white" 
                        @click="approveDeposit" 
                        :disabled="!selectedDeposit || isApproving">
                        <template v-if="isApproving">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </template>
                        <template v-else>
                            Aprobar Depósito
                        </template>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal de confirmación para rechazar -->
        <Dialog :open="showRejectDialog" @update:open="showRejectDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rechazar Depósito</DialogTitle>
                    <DialogDescription>
                        Por favor, indique el motivo del rechazo.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="selectedDeposit" class="py-4">
                    <div class="bg-gray-50 p-3 rounded-lg mb-4">
                        <div class="flex flex-col space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Usuario</p>
                                    <p class="text-sm text-gray-900">{{ selectedDeposit.user.name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Email</p>
                                    <p class="text-sm text-gray-500">{{ selectedDeposit.user.email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Membresía</p>
                                    <p class="text-sm">
                                        <span v-if="selectedDeposit.user.membership" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ selectedDeposit.user.membership.nombre }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Sin membresía
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Monto</p>
                                    <p class="text-sm font-medium text-red-600">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(selectedDeposit.amount) }}</p>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Banco</p>
                                    <p class="text-sm">{{ selectedDeposit.bank_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Referencia</p>
                                    <p class="text-sm">{{ selectedDeposit.transaction_reference }}</p>
                                </div>
                                <div>
                                    <a :href="`/storage/${selectedDeposit.receipt_image}`" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver comprobante
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <Label for="rejection_reason">Motivo del rechazo</Label>
                        <Textarea 
                            id="rejection_reason" 
                            v-model="rejectForm.rejection_reason" 
                            rows="3"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500': rejectForm.errors.rejection_reason }"
                            required
                        ></Textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="mt-1 text-sm text-red-600">{{ rejectForm.errors.rejection_reason }}</p>
                    </div>
                </div>
                <DialogFooter class="flex justify-end space-x-2">
                    <Button variant="outline" @click="showRejectDialog = false">Cancelar</Button>
                    <Button 
                        class="bg-red-600 hover:bg-red-700 text-white" 
                        @click="rejectDeposit" 
                        :disabled="!selectedDeposit || rejectForm.processing || !rejectForm.rejection_reason"
                    >
                        {{ rejectForm.processing ? 'Procesando...' : 'Rechazar Depósito' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal de detalles del depósito -->
        <Dialog :open="showDetailsDialog" @update:open="showDetailsDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Detalles del Depósito</DialogTitle>
                    <DialogDescription>
                        Información completa sobre la solicitud de depósito
                    </DialogDescription>
                </DialogHeader>
                <div v-if="detailsDeposit" class="py-4">
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Información del Usuario</h3>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Nombre</p>
                                    <p class="text-sm text-gray-900">{{ detailsDeposit.user.name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Email</p>
                                    <p class="text-sm text-gray-900">{{ detailsDeposit.user.email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Membresía</p>
                                    <p class="text-sm">
                                        <span v-if="detailsDeposit.user.membership" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ detailsDeposit.user.membership.nombre }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Sin membresía
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <p class="text-xs font-medium text-gray-500 mb-1">Saldos actuales</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-white p-2 rounded border">
                                        <p class="text-xs text-gray-500">Capital</p>
                                        <p class="text-sm font-medium">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(detailsDeposit.user.capital_balance || 0) }}</p>
                                    </div>
                                    <div class="bg-white p-2 rounded border">
                                        <p class="text-xs text-gray-500">Ganancias</p>
                                        <p class="text-sm font-medium">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(detailsDeposit.user.earnings_balance || 0) }}</p>
                                    </div>
                                    <div class="bg-white p-2 rounded border">
                                        <p class="text-xs text-gray-500">Red</p>
                                        <p class="text-sm font-medium">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(detailsDeposit.user.network_balance || 0) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Información del Depósito</h3>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Fecha de Solicitud</p>
                                    <p class="text-sm">{{ formatDate(detailsDeposit.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Estado</p>
                                    <p class="text-sm">
                                        <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(detailsDeposit.status)}`">
                                            {{ getStatusText(detailsDeposit.status) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Monto</p>
                                    <p class="text-sm font-medium text-green-600">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(detailsDeposit.amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Tipo</p>
                                    <p class="text-sm">{{ formatBalanceType(detailsDeposit.balance_type) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Banco</p>
                                    <p class="text-sm">{{ detailsDeposit.bank_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Referencia</p>
                                    <p class="text-sm">{{ detailsDeposit.transaction_reference }}</p>
                                </div>
                                <div class="col-span-2" v-if="detailsDeposit.processed_at">
                                    <p class="text-xs font-medium text-gray-500">Procesado el</p>
                                    <p class="text-sm">{{ formatDate(detailsDeposit.processed_at) }}</p>
                                </div>
                                <div class="col-span-2" v-if="detailsDeposit.notes">
                                    <p class="text-xs font-medium text-gray-500">Notas</p>
                                    <p class="text-sm whitespace-pre-line">{{ detailsDeposit.notes }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-2 flex justify-between items-center">
                                <a :href="`/storage/${detailsDeposit.receipt_image}`" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Ver comprobante
                                </a>
                                
                                <div v-if="detailsDeposit.status === 'pendiente'" class="flex space-x-2">
                                    <Button
                                        size="sm"
                                        class="bg-green-600 hover:bg-green-700 text-white"
                                        @click="openApproveDialog(detailsDeposit); showDetailsDialog = false;"
                                        :disabled="isApproving"
                                    >
                                        Aprobar
                                    </Button>
                                    <Button
                                        size="sm"
                                        class="bg-red-600 hover:bg-red-700 text-white"
                                        @click="openRejectDialog(detailsDeposit); showDetailsDialog = false;"
                                        :disabled="isApproving"
                                    >
                                        Rechazar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter class="flex justify-end">
                    <Button variant="outline" @click="showDetailsDialog = false">Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template> 