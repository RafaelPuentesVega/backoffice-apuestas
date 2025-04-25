<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { LineChart, PieChart, BarChart } from '@/components/ui/charts';
import { ArrowUpRight, ArrowDownRight, TrendingUp, Coins, Users, Ban, Clock, CheckCircle, XCircle } from 'lucide-vue-next';

interface Transaction {
    id: number;
    transaction_type: string;
    balance_type: string;
    amount: number;
    status: string;
    description: string;
    created_at: string;
}

interface Deposit {
    id: number;
    amount: number;
    status: string;
    created_at: string;
}

interface Withdrawal {
    id: number;
    amount: number;
    status: string;
    created_at: string;
}

interface ChartData {
    date: string;
    deposits: number;
    withdrawals: number;
}

interface Props {
    isAdmin: boolean;
    user: {
        id: number;
        name: string;
        email: string;
        membership: {
            id: number;
            nombre: string;
            precio: number;
        } | null;
        pendingMembership?: {
            id: number;
            nombre: string;
            precio: number;
        } | null;
    };
    balances: {
        capital: number;
        earnings: number;
        network: number;
        total: number;
    };
    latestTransactions: Transaction[];
    recentDeposits: Deposit[];
    recentWithdrawals: Withdrawal[];
    lastWeekStats: ChartData[];
    referralsStats: {
        total: number;
        active: number;
    };
    upcomingEvents: any[];
    pendingMembership: {
        id: number;
        nombre: string;
        precio: number;
    } | null;
    pendingMembershipPayment: {
        id: number;
        status: string;
        created_at: string;
    } | null;
}

const props = defineProps<Props>();

// Filtrar transacciones para excluir withdrawal_refund
const filteredTransactions = computed(() => {
    return props.latestTransactions.filter(transaction => 
        transaction.transaction_type !== 'withdrawal_refund'
    );
});

// Preparar datos para gráficos
const chartData = {
    labels: props.lastWeekStats.map(item => item.date),
    datasets: [
        {
            label: 'Depósitos',
            data: props.lastWeekStats.map(item => item.deposits),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
        },
        {
            label: 'Retiros',
            data: props.lastWeekStats.map(item => item.withdrawals),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.2)',
        }
    ]
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-AR', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (status) => {
    const statusLower = String(status).toLowerCase();
    if (statusLower.includes('pend') || statusLower === 'pending') return 'text-yellow-500';
    if (statusLower.includes('aprob') || statusLower === 'approved' || statusLower === 'completado' || statusLower === 'completed') return 'text-green-500';
    if (statusLower.includes('recha') || statusLower === 'rejected') return 'text-red-500';
    return 'text-gray-500';
};

const getStatusIcon = (status) => {
    const statusLower = String(status).toLowerCase();
    if (statusLower.includes('pend') || statusLower === 'pending') return Clock;
    if (statusLower.includes('aprob') || statusLower === 'approved' || statusLower === 'completado' || statusLower === 'completed') return CheckCircle;
    if (statusLower.includes('recha') || statusLower === 'rejected') return XCircle;
    return Ban;
};

// Traducir el estado para mostrar correctamente en español
const getStatusLabel = (status) => {
    const statusLower = String(status).toLowerCase();
    if (statusLower.includes('pend') || statusLower === 'pending') return 'pendiente';
    if (statusLower.includes('aprob') || statusLower === 'approved') return 'aprobado';
    if (statusLower === 'completed' || statusLower === 'completado') return 'completado';  
    if (statusLower.includes('recha') || statusLower === 'rejected') return 'rechazado';
    return statusLower;
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    }
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <!-- Bienvenida y resumen -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-2">¡Bienvenido, {{ user.name }}!</h1>
                <div v-if="user.membership" class="text-gray-600">
                    Membresía actual: <span class="font-medium">{{ user.membership.nombre }}</span>
                    <Button variant="link" class="text-primary p-0 ml-2" @click="$inertia.visit('/membership/history')">
                        Ver historial
                    </Button>
                </div>
                
                <!-- Alerta de membresía pendiente -->
                <div v-if="pendingMembership" class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <Clock class="h-8 w-8 text-yellow-500" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-yellow-800">Membresía pendiente de aprobación</h3>
                            <div class="mt-2 text-yellow-700">
                                <p>Tu solicitud para la membresía <strong>{{ pendingMembership.nombre }}</strong> está pendiente de aprobación.</p>
                                <p v-if="pendingMembershipPayment" class="mt-2">
                                    Solicitud enviada el {{ formatDate(pendingMembershipPayment.created_at) }}.
                                </p>
                                <div class="mt-3">
                                    <Button variant="outline" class="border-yellow-500 text-yellow-700" @click="$inertia.visit('/membership/history')">
                                        Ver detalles
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else-if="!user.membership" class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-red-800">¡No tienes una membresía activa!</h3>
                            <div class="mt-2 text-red-700">
                                <p>Para acceder a todas las funciones de la plataforma y comenzar a generar ganancias, es necesario que adquieras una membresía.</p>
                                <div class="mt-3">
                                    <Button class="bg-red-600 hover:bg-red-700 text-white" @click="$inertia.visit('/membership/select')">
                                        Seleccionar membresía ahora
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de saldo -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Balance Total</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-center">
                            <div class="text-2xl font-bold">{{ formatCurrency(balances.total) }}</div>
                            <div class="bg-blue-100 p-2 rounded-full text-blue-600">
                                <Coins class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Capital</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-center">
                            <div class="text-2xl font-bold">{{ formatCurrency(balances.capital) }}</div>
                            <div class="bg-emerald-100 p-2 rounded-full text-emerald-600">
                                <ArrowUpRight class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Ganancias</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-center">
                            <div class="text-2xl font-bold">{{ formatCurrency(balances.earnings) }}</div>
                            <div class="bg-purple-100 p-2 rounded-full text-purple-600">
                                <TrendingUp class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">Comisiones de Red</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-center">
                            <div class="text-2xl font-bold">{{ formatCurrency(balances.network) }}</div>
                            <div class="bg-amber-100 p-2 rounded-full text-amber-600">
                                <Users class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Gráfico de actividad y referidos -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Actividad de los últimos 7 días</CardTitle>
                        <CardDescription>Depósitos y retiros</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <LineChart 
                            :chart-data="chartData" 
                            :chart-options="{
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }"
                            :height="250"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Referidos</CardTitle>
                        <CardDescription>Resumen de tu red</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total de referidos:</span>
                                <span class="font-bold">{{ referralsStats.total }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Referidos activos:</span>
                                <span class="font-bold">{{ referralsStats.active }}</span>
                            </div>
                            
                            <!-- Gráfico de dona para referidos -->
                            <div v-if="referralsStats.total > 0" class="h-48">
                                <PieChart 
                                    :chart-data="{
                                        labels: ['Activos', 'Inactivos'],
                                        datasets: [{
                                            data: [referralsStats.active, referralsStats.total - referralsStats.active],
                                            backgroundColor: ['#10b981', '#d1d5db']
                                        }]
                                    }"
                                    :height="150"
                                    :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                    }"
                                />
                            </div>
                            <div v-else class="text-center py-6 text-gray-500">
                                No tienes referidos aún
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" class="w-full" @click="$inertia.visit('/network')">
                            Ver detalles de mi red
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Últimas transacciones y acciones rápidas -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Últimas Transacciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transaction in filteredTransactions" :key="transaction.id">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            {{ getTransactionTypeLabel(transaction.transaction_type) }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap font-medium">
                                            {{ formatCurrency(transaction.amount) }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            {{ getBalanceTypeLabel(transaction.balance_type) }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <component :is="getStatusIcon(transaction.status)" class="h-4 w-4 mr-1" :class="getStatusColor(transaction.status)" />
                                                <span :class="getStatusColor(transaction.status)">
                                                    {{ getStatusLabel(transaction.status) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(transaction.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="filteredTransactions.length === 0">
                                        <td colspan="5" class="px-3 py-4 text-center text-gray-500">
                                            No hay transacciones recientes
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" class="w-full" @click="$inertia.visit('/transactions')">
                            Ver todas las transacciones
                        </Button>
                    </CardFooter>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Acciones Rápidas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <Button variant="default" class="w-full flex justify-between items-center" @click="$inertia.visit('/deposit')">
                                <span>Realizar Depósito</span>
                                <ArrowUpRight class="h-4 w-4" />
                            </Button>
                            
                            <Button variant="outline" class="w-full flex justify-between items-center" @click="$inertia.visit('/withdrawal/create')">
                                <span>Solicitar Retiro</span>
                                <ArrowDownRight class="h-4 w-4" />
                            </Button>
                            
                            <Button variant="outline" class="w-full flex justify-between items-center" @click="$inertia.visit('/settings/wallet')">
                                <span>Configurar Billetera</span>
                                <Coins class="h-4 w-4" />
                            </Button>
                            
                            <Button variant="outline" class="w-full flex justify-between items-center" @click="$inertia.visit('/network')">
                                <span>Gestionar Red</span>
                                <Users class="h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
