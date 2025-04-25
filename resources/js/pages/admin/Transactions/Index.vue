<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
    Card, 
    CardContent, 
    CardDescription, 
    CardFooter, 
    CardHeader, 
    CardTitle 
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Calendar } from '@/components/ui/calendar';
import { CalendarIcon, Search, ArrowLeft } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Transaction {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    transaction_type: string;
    balance_type: string;
    amount: number;
    status: string;
    description: string;
    created_at: string;
    balance_after: number;
}

interface Props {
    transactions: {
        data: Transaction[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    stats: {
        total: number;
        depositos: number;
        retiros: number;
        comisiones: number;
    };
    filters: {
        search: string;
        type: string;
        status: string;
        date_from: string;
        date_to: string;
    };
}

const props = defineProps<Props>();

// Manejo de filtros
const filters = ref({
    search: props.filters.search || '',
    type: props.filters.type || '',
    status: props.filters.status || '',
    date_from: props.filters.date_from ? new Date(props.filters.date_from) : null,
    date_to: props.filters.date_to ? new Date(props.filters.date_to) : null,
});

watch(filters, (newFilters) => {
    router.get(
        route('admin.transactions.index'),
        {
            search: newFilters.search,
            type: newFilters.type,
            status: newFilters.status,
            date_from: newFilters.date_from ? formatDate(newFilters.date_from) : '',
            date_to: newFilters.date_to ? formatDate(newFilters.date_to) : '',
        },
        {
            preserveState: true,
            replace: true,
        }
    );
}, { deep: true });

// Utilidades
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(value);
};

const formatDate = (dateString) => {
    const date = dateString instanceof Date ? dateString : new Date(dateString);
    return date.toISOString().split('T')[0]; // Formato YYYY-MM-DD
};

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-AR', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTransactionTypeLabel = (type) => {
    switch (type) {
        case 'deposit': return 'Depósito';
        case 'withdrawal': return 'Retiro';
        case 'commission': return 'Comisión';
        case 'membership': return 'Membresía';
        default: return type;
    }
};

const getBalanceTypeLabel = (type) => {
    switch (type) {
        case 'capital': return 'Capital';
        case 'earnings': return 'Ganancias';
        case 'network': return 'Red';
        default: return type;
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'pendiente': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'completado': return 'bg-green-100 text-green-800 border-green-200';
        case 'rechazado': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de Administración',
        href: '/admin/dashboard',
    },
    {
        title: 'Transacciones',
        href: '/admin/transactions',
    },
];

// Limpiar filtros
const clearFilters = () => {
    filters.value = {
        search: '',
        type: '',
        status: '',
        date_from: null,
        date_to: null,
    };
};
</script>

<template>
    <Head title="Transacciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Historial de Transacciones</h1>
                    <p class="text-gray-500">
                        Listado de todas las transacciones del sistema
                    </p>
                </div>
                <Link :href="route('admin.dashboard')" class="flex items-center text-gray-600 hover:text-black">
                    <ArrowLeft class="w-4 h-4 mr-1" />
                    Volver al Dashboard
                </Link>
            </div>

            <!-- Tarjetas de estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Total Transacciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Depósitos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.depositos }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Retiros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.retiros }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Comisiones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.comisiones }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    v-model="filters.search"
                                    placeholder="Buscar por nombre o email"
                                    class="pl-9"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Transacción</label>
                            <Select v-model="filters.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos los tipos</SelectItem>
                                    <SelectItem value="deposit">Depósito</SelectItem>
                                    <SelectItem value="withdrawal">Retiro</SelectItem>
                                    <SelectItem value="commission">Comisión</SelectItem>
                                    <SelectItem value="membership">Membresía</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <Select v-model="filters.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos los estados</SelectItem>
                                    <SelectItem value="pendiente">Pendiente</SelectItem>
                                    <SelectItem value="completado">Completado</SelectItem>
                                    <SelectItem value="rechazado">Rechazado</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Button variant="outline" class="w-full mt-6" @click="clearFilters">
                                Limpiar Filtros
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de transacciones -->
            <Card>
                <CardHeader>
                    <CardTitle>Listado de Transacciones</CardTitle>
                    <CardDescription>
                        Mostrando {{ transactions.from }}-{{ transactions.to }} de {{ transactions.total }} transacciones
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="transaction in transactions.data" :key="transaction.id">
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        {{ transaction.id }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ transaction.user.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 ml-1">
                                                ({{ transaction.user.email }})
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        {{ getTransactionTypeLabel(transaction.transaction_type) }}
                                        <span class="text-xs text-gray-500 block">
                                            {{ getBalanceTypeLabel(transaction.balance_type) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap font-medium" :class="transaction.amount >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(transaction.amount) }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        {{ formatCurrency(transaction.balance_after) }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <Badge :class="getStatusColor(transaction.status)">
                                            {{ transaction.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDateTime(transaction.created_at) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-500 max-w-xs truncate">
                                        {{ transaction.description }}
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="8" class="px-3 py-4 text-center text-gray-500">
                                        No hay transacciones que coincidan con los filtros
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
                <CardFooter>
                    <!-- Paginación simple -->
                    <div class="flex justify-between items-center w-full">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            :disabled="transactions.current_page === 1"
                            @click="router.get(route('admin.transactions.index', {
                                page: transactions.current_page - 1,
                                ...filters
                            }))"
                        >
                            Anterior
                        </Button>
                        
                        <span class="text-sm text-gray-600">
                            Página {{ transactions.current_page }} de {{ transactions.last_page }}
                        </span>
                        
                        <Button 
                            variant="outline" 
                            size="sm" 
                            :disabled="transactions.current_page === transactions.last_page"
                            @click="router.get(route('admin.transactions.index', {
                                page: transactions.current_page + 1,
                                ...filters
                            }))"
                        >
                            Siguiente
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template> 