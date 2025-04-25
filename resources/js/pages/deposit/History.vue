<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/card/Card.vue';
import CardHeader from '@/Components/ui/card/CardHeader.vue';
import CardTitle from '@/Components/ui/card/CardTitle.vue';
import CardContent from '@/Components/ui/card/CardContent.vue';
import CardFooter from '@/Components/ui/card/CardFooter.vue';
import Button from '@/Components/ui/button/Button.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';

interface Deposit {
    id: number;
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
    deposits: Deposit[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: route('dashboard') },
    { label: 'Depósitos', href: route('deposit.create') },
    { label: 'Historial', href: route('deposit.history') }
];

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
</script>

<template>
    <Head title="Historial de Depósitos" />

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

                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900">Historial de Depósitos</h1>
                    <Button as="a" :href="route('deposit.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                        Nuevo Depósito
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Historial de Transacciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.deposits.length === 0" class="text-center py-8 text-gray-500">
                            No hay depósitos registrados todavía.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banco</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="deposit in props.deposits" :key="deposit.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(deposit.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(deposit.amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatBalanceType(deposit.balance_type) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ deposit.bank_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(deposit.status)}`">
                                                {{ getStatusText(deposit.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center space-x-2">
                                                <a :href="route('deposit.receipt', deposit.id)" target="_blank" class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Ver comprobante
                                                </a>
                                                <span v-if="deposit.status === 'rechazado' && deposit.notes" class="text-gray-500 cursor-help group relative">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                    </svg>
                                                    <div class="absolute hidden group-hover:block w-64 bg-white border border-gray-200 p-2 rounded shadow-lg text-xs text-left whitespace-normal z-10 -mt-1 -ml-72">
                                                        {{ deposit.notes }}
                                                    </div>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 