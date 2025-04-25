<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/card/Card.vue';
import CardHeader from '@/Components/ui/card/CardHeader.vue';
import CardTitle from '@/Components/ui/card/CardTitle.vue';
import CardContent from '@/Components/ui/card/CardContent.vue';
import CardFooter from '@/Components/ui/card/CardFooter.vue';
import Button from '@/Components/ui/button/Button.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/Components/ui/label/Label.vue';
import Separator from '@/Components/ui/separator/Separator.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';

interface Transaction {
    id: number;
    type: string;
    amount: number;
    description: string;
    created_at: string;
}

interface ProfitDistribution {
    id: number;
    amount: number;
    source_type: string;
    created_at: string;
}

interface Props {
    transactions: Transaction[];
    totals: {
        period: number;
        capital: number;
        earnings: number;
        network: number;
    };
    profit_distributions?: ProfitDistribution[];
    filters: {
        date_from?: string;
        date_to?: string;
        transaction_type?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: route('dashboard') },
    { label: 'Transacciones', href: route('transactions.index') }
];

// Filtros
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const transactionType = ref(props.filters.transaction_type || '');

// Tipos de transacciones para el filtro
const transactionTypes = [
    { value: '', label: 'Todas' },
    { value: 'deposit', label: 'Depósito' },
    { value: 'withdrawal', label: 'Retiro' },
    { value: 'profit', label: 'Ganancias' },
    { value: 'commission', label: 'Comisión' },
];

function formatTransactionType(type: string): string {
    switch (type) {
        case 'deposit': return 'Depósito';
        case 'withdrawal': return 'Retiro';
        case 'profit': return 'Ganancia';
        case 'commission': return 'Comisión';
        default: return type;
    }
}

function getTransactionColor(type: string): string {
    switch (type) {
        case 'deposit': return 'bg-blue-100 text-blue-800';
        case 'withdrawal': return 'bg-red-100 text-red-800';
        case 'profit': return 'bg-green-100 text-green-800';
        case 'commission': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getTransactionIcon(type: string): string {
    switch (type) {
        case 'deposit': return '↑';
        case 'withdrawal': return '↓';
        case 'profit': return '↗';
        case 'commission': return '↗';
        default: return '•';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function updateFilters() {
    router.get(route('transactions.index'), {
        date_from: dateFrom.value,
        date_to: dateTo.value,
        transaction_type: transactionType.value,
    }, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Historial de Transacciones" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem v-for="(item, index) in breadcrumbs" :key="index">
                                <Link v-if="index < breadcrumbs.length - 1" :href="item.href" class="text-gray-500 hover:text-gray-700">
                                    {{ item.label }}
                                </Link>
                                <span v-else class="font-medium text-gray-900">{{ item.label }}</span>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>

                <h1 class="text-2xl font-semibold text-gray-900 mb-6">Historial de Transacciones</h1>

                <!-- Tarjetas de resumen -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Total en periodo</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.period) }}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Capital</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.capital) }}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Ganancias</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.earnings) }}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Comisiones de Red</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.network) }}</div>
                        </CardContent>
                    </Card>
                </div>
                
                <!-- Filtros -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <Label for="date-from">Desde</Label>
                                <Input 
                                    id="date-from" 
                                    v-model="dateFrom" 
                                    type="date" 
                                    class="mt-1 block w-full"
                                />
                            </div>
                            
                            <div>
                                <Label for="date-to">Hasta</Label>
                                <Input 
                                    id="date-to" 
                                    v-model="dateTo" 
                                    type="date" 
                                    class="mt-1 block w-full"
                                />
                            </div>
                            
                            <div>
                                <Label for="transaction-type">Tipo de Transacción</Label>
                                <select 
                                    id="transaction-type" 
                                    v-model="transactionType"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option v-for="type in transactionTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button @click="updateFilters">Aplicar Filtros</Button>
                    </CardFooter>
                </Card>

                <!-- Historial de transacciones -->
                <Card>
                    <CardHeader>
                        <CardTitle>Historial</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="props.transactions.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No hay transacciones que mostrar en este periodo.
                                        </td>
                                    </tr>
                                    <tr v-for="transaction in props.transactions" :key="transaction.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(transaction.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTransactionColor(transaction.type)}`">
                                                {{ getTransactionIcon(transaction.type) }} {{ formatTransactionType(transaction.type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ transaction.description }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" 
                                            :class="['withdrawal'].includes(transaction.type) ? 'text-red-600' : 'text-green-600'">
                                            {{ ['withdrawal'].includes(transaction.type) ? '-' : '+' }}
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(transaction.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <div>
                            <Link :href="route('transactions.report')" class="text-indigo-600 hover:text-indigo-900">
                                Ver reporte consolidado
                            </Link>
                        </div>
                        <div>
                            <Link :href="route('transactions.network')" class="text-indigo-600 hover:text-indigo-900">
                                Ver comisiones de red
                            </Link>
                        </div>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 