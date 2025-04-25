<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { LineChart, PieChart, BarChart } from '@/components/ui/charts';
import { 
    Users, 
    DollarSign, 
    ArrowUpRight, 
    ArrowDownRight, 
    TrendingUp, 
    Wallet, 
    AlertCircle,
    CreditCard,
    Percent,
    Award,
    UserPlus,
    Activity,
    Shield
} from 'lucide-vue-next';

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
}

interface UserGrowth {
    date: string;
    newUsers: number;
}

interface TopUser {
    id: number;
    name: string;
    email: string;
    totalBalance: number;
}

interface MembershipData {
    name: string;
    count: number;
}

interface PlatformProfit {
    month: string;
    profit: number;
    userEarnings: number;
}

interface Props {
    isAdmin: boolean;
    stats: {
        totalUsers: number;
        activeUsers: number;
        inactiveUsers: number;
        depositsStats: {
            pending: number;
            approved: number;
            rejected: number;
            totalAmount: number;
        };
        withdrawalsStats: {
            pending: number;
            approved: number;
            rejected: number;
            totalAmount: number;
        };
        platformStats: {
            totalProfit: number;
            profitThisMonth: number;
            profitGrowth: number;
            userEarnings: number;
            userEarningsGrowth: number;
            commissionPaid: number;
            feeCollected: number;
        };
    };
    latestTransactions: Transaction[];
    userGrowth: UserGrowth[];
    topUsersByBalance: TopUser[];
    topUsersByEarnings: TopUser[];
    membershipDistribution: MembershipData[];
    profitTrend: PlatformProfit[];
    pendingMemberships: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
        membership: {
            id: number;
            nombre: string;
            precio: number;
        };
        deposit: {
            id: number;
            amount: number;
            bank_name: string;
            transaction_reference: string;
            receipt_image: string;
            notes: string;
            created_at: string;
        };
    }>;
}

const props = defineProps<Props>();

// Calcular estadísticas adicionales
const totalMembershipsAmount = ref(0);

// Buscar transacciones de membresía para calcular el total
if (props.latestTransactions && props.latestTransactions.length > 0) {
    totalMembershipsAmount.value = props.latestTransactions
        .filter(tx => tx.transaction_type === 'membership_payment')
        .reduce((total, tx) => total + tx.amount, 0);
}

// Preparar datos para gráficos de crecimiento de usuarios
const userGrowthData = {
    labels: props.userGrowth.map(item => item.date),
    datasets: [
        {
            label: 'Nuevos usuarios',
            data: props.userGrowth.map(item => item.newUsers),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            tension: 0.4
        }
    ]
};

// Preparar datos para gráfico de distribución de membresías
const membershipData = {
    labels: props.membershipDistribution.map(item => item.name),
    datasets: [
        {
            data: props.membershipDistribution.map(item => item.count),
            backgroundColor: [
                '#10b981', // green
                '#3b82f6', // blue
                '#f59e0b', // amber
                '#ef4444', // red
                '#8b5cf6', // purple
                '#ec4899', // pink
                '#6b7280', // gray
            ],
            borderWidth: 1
        }
    ]
};

// Preparar datos para gráfico de ganancias de plataforma vs usuarios
const profitData = {
    labels: props.profitTrend?.map(item => item.month) || [],
    datasets: [
        {
            label: 'Ganancia Plataforma',
            data: props.profitTrend?.map(item => item.profit) || [],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            tension: 0.4
        },
        {
            label: 'Ganancias Usuarios',
            data: props.profitTrend?.map(item => item.userEarnings) || [],
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.2)',
            tension: 0.4
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

const formatPercent = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    }).format(value/100);
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
        title: 'Panel de Administración',
        href: '/dashboard',
    }
];
</script>

<template>
    <Head title="Panel de Administración" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <!-- Cabecera -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-2">Panel de Administración</h1>
                <p class="text-gray-600">
                    Visión general del sistema y estadísticas clave
                </p>
            </div>

            <!-- Estadísticas de Plataforma -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <DollarSign class="h-5 w-5 mr-2 text-primary" />
                    Rendimiento Financiero de la Plataforma
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card class="bg-green-50 border-green-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg text-green-700">Ganancia Total</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold text-green-700">{{ formatCurrency(stats.platformStats.totalProfit) }}</div>
                                <div class="bg-green-100 p-2 rounded-full text-green-600">
                                    <DollarSign class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-green-700 mt-2">
                                Este mes: {{ formatCurrency(stats.platformStats.profitThisMonth) }}
                                <span class="ml-1" :class="stats.platformStats.profitGrowth >= 0 ? 'text-green-700' : 'text-red-700'">
                                    ({{ stats.platformStats.profitGrowth >= 0 ? '+' : '' }}{{ formatPercent(stats.platformStats.profitGrowth) }})
                                </span>
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="bg-amber-50 border-amber-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg text-amber-700">Ganancias Usuarios</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold text-amber-700">{{ formatCurrency(stats.platformStats.userEarnings) }}</div>
                                <div class="bg-amber-100 p-2 rounded-full text-amber-600">
                                    <Wallet class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-amber-700 mt-2">
                                Crecimiento: 
                                <span :class="stats.platformStats.userEarningsGrowth >= 0 ? 'text-green-700' : 'text-red-700'">
                                    {{ stats.platformStats.userEarningsGrowth >= 0 ? '+' : '' }}{{ formatPercent(stats.platformStats.userEarningsGrowth) }}
                                </span>
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="bg-blue-50 border-blue-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg text-blue-700">Comisiones Pagadas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold text-blue-700">{{ formatCurrency(stats.platformStats.commissionPaid) }}</div>
                                <div class="bg-blue-100 p-2 rounded-full text-blue-600">
                                    <Award class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-blue-700 mt-2">
                                Total pagado a referidos
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="bg-purple-50 border-purple-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg text-purple-700">Fees Cobrados</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold text-purple-700">{{ formatCurrency(stats.platformStats.feeCollected) }}</div>
                                <div class="bg-purple-100 p-2 rounded-full text-purple-600">
                                    <Percent class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-purple-700 mt-2">
                                Comisiones por transacciones
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Estadísticas de Usuarios -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <Users class="h-5 w-5 mr-2 text-primary" />
                    Estadísticas de Usuarios
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg">Usuarios Totales</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold">{{ stats.totalUsers }}</div>
                                <div class="bg-blue-100 p-2 rounded-full text-blue-600">
                                    <Users class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ stats.activeUsers }} usuarios activos ({{ ((stats.activeUsers / stats.totalUsers) * 100).toFixed(1) }}%)
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg">Depósitos Pendientes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold">{{ stats.depositsStats.pending }}</div>
                                <div class="bg-amber-100 p-2 rounded-full text-amber-600">
                                    <ArrowUpRight class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                Total: {{ formatCurrency(stats.depositsStats.totalAmount) }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg">Retiros Pendientes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold">{{ stats.withdrawalsStats.pending }}</div>
                                <div class="bg-purple-100 p-2 rounded-full text-purple-600">
                                    <ArrowDownRight class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                Aprobados: {{ stats.withdrawalsStats.approved }} este mes
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Tarjeta de total de membresías pagadas -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <Award class="h-5 w-5 mr-2 text-primary" />
                    Estadísticas de Membresías
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card class="bg-green-50 border-green-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg text-green-700">Total de Pagos por Membresías</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold text-green-700">{{ formatCurrency(totalMembershipsAmount) }}</div>
                                <div class="bg-green-100 p-2 rounded-full text-green-600">
                                    <Award class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-green-700 mt-2">
                                Acumulado de todos los pagos por membresías
                            </p>
                        </CardContent>
                    </Card>
                    
                    <Card class="bg-blue-50 border-blue-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg text-blue-700">Membresías Activas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex justify-between items-center">
                                <div class="text-2xl font-bold text-blue-700">{{ stats.activeUsers }}</div>
                                <div class="bg-blue-100 p-2 rounded-full text-blue-600">
                                    <Shield class="h-6 w-6" />
                                </div>
                            </div>
                            <p class="text-sm text-blue-700 mt-2">
                                Usuarios con membresía activa
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Tarjetas de acciones importantes -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
                <Card v-if="stats.depositsStats.pending > 0" class="bg-amber-50 border-amber-200">
                    <CardHeader>
                        <CardTitle class="flex items-center text-amber-700">
                            <AlertCircle class="h-5 w-5 mr-2" />
                            Depósitos Pendientes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-amber-700">
                            Hay {{ stats.depositsStats.pending }} depósitos pendientes de aprobación.
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" class="w-full bg-white text-amber-700 border-amber-300 hover:bg-amber-100" @click="$inertia.visit(route('admin.deposits'))">
                            Gestionar Depósitos
                        </Button>
                    </CardFooter>
                </Card>

                <Card v-if="stats.withdrawalsStats.pending > 0" class="bg-purple-50 border-purple-200">
                    <CardHeader>
                        <CardTitle class="flex items-center text-purple-700">
                            <AlertCircle class="h-5 w-5 mr-2" />
                            Retiros Pendientes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-purple-700">
                            Hay {{ stats.withdrawalsStats.pending }} retiros pendientes de aprobación.
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" class="w-full bg-white text-purple-700 border-purple-300 hover:bg-purple-100" @click="$inertia.visit(route('admin.withdrawals.index'))">
                            Gestionar Retiros
                        </Button>
                    </CardFooter>
                </Card>

                <Card class="bg-blue-50 border-blue-200">
                    <CardHeader>
                        <CardTitle class="flex items-center text-blue-700">
                            <TrendingUp class="h-5 w-5 mr-2" />
                            Configuraciones
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-blue-700">
                            Accede a las configuraciones del sistema y parámetros.
                        </p>
                    </CardContent>
                    <CardFooter class="flex justify-between gap-2">
                        <Button variant="outline" class="w-full bg-white text-blue-700 border-blue-300 hover:bg-blue-100" @click="$inertia.visit(route('admin.settings.deposits'))">
                            Config. Depósitos
                        </Button>
                        <Button variant="outline" class="w-full bg-white text-blue-700 border-blue-300 hover:bg-blue-100" @click="$inertia.visit(route('admin.settings.withdrawals'))">
                            Config. Retiros
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Membresías Pendientes -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Membresías Pendientes de Aprobación</h2>
                    
                    <div v-if="pendingMemberships.length === 0" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-gray-500 dark:text-gray-400">No hay membresías pendientes de aprobación</p>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="item in pendingMemberships" :key="item.id" class="bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow">
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-semibold text-lg">{{ item.user?.name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.user?.email }}</p>
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                        {{ formatCurrency(item.membership?.precio) }}
                                    </span>
                                </div>
                                
                                <div class="mb-2">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Membresía:</span> {{ item.membership?.nombre }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Banco:</span> {{ item.deposit?.bank_name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Referencia:</span> {{ item.deposit?.transaction_reference }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Fecha:</span> {{ formatDate(item.deposit?.created_at) }}
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <a :href="`/storage/${item.deposit?.receipt_image}`" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                        Ver comprobante de pago
                                    </a>
                                    <p v-if="item.deposit?.notes" class="mt-2 text-sm text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 p-2 rounded">
                                        {{ item.deposit?.notes }}
                                    </p>
                                </div>
                                
                                <div class="flex gap-2">
                                    <form v-if="item.user && item.user.id" :action="route('admin.memberships.approve', item.user.id)" method="POST">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token">
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                            Aprobar
                                        </button>
                                    </form>
                                    
                                    <form v-if="item.user && item.user.id" :action="route('admin.memberships.reject', item.user.id)" method="POST">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token">
                                        <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                            Rechazar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos y estadísticas -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Gráfico de tendencia de ganancias -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Tendencia de Ganancias (últimos 6 meses)</CardTitle>
                        <CardDescription>Comparativa ganancias plataforma vs usuarios</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <LineChart 
                            :chart-data="profitData" 
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

                <!-- Gráfico de distribución de membresías -->
                <Card>
                    <CardHeader>
                        <CardTitle>Distribución de Membresías</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="membershipDistribution.length > 0" class="h-[250px]">
                            <PieChart 
                                :chart-data="membershipData"
                                :height="250"
                            />
                        </div>
                        <div v-else class="h-[250px] flex items-center justify-center">
                            <p class="text-gray-500">No hay datos disponibles</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Gráfico de crecimiento de usuarios y Top usuarios -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Nuevos Usuarios (Últimos 30 días)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <LineChart 
                            :chart-data="userGrowthData" 
                            :chart-options="{
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0 // Mostrar solo enteros
                                        }
                                    }
                                }
                            }"
                            :height="250"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Top 5 Usuarios por Balance</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-4">
                            <li v-for="(user, index) in topUsersByBalance" :key="user.id" class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold mr-3">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium">{{ user.name }}</h4>
                                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium">{{ formatCurrency(user.totalBalance) }}</div>
                                    </div>
                                </div>
                            </li>
                            <li v-if="topUsersByBalance.length === 0" class="p-4 text-center text-gray-500">
                                No hay datos disponibles
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <!-- Top usuarios por ganancias y últimas transacciones -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Top 5 Usuarios por Ganancias</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-4">
                            <li v-for="(user, index) in topUsersByEarnings" :key="user.id" class="p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-semibold mr-3">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium">{{ user.name }}</h4>
                                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-green-600">{{ formatCurrency(user.totalBalance) }}</div>
                                    </div>
                                </div>
                            </li>
                            <li v-if="topUsersByEarnings.length === 0" class="p-4 text-center text-gray-500">
                                No hay datos disponibles
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Últimas Transacciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transaction in latestTransactions" :key="transaction.id">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ transaction.user?.name }}
                                                </div>
                                                <div class="text-sm text-gray-500 ml-1">
                                                    ({{ transaction.user?.email }})
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            {{ getTransactionTypeLabel(transaction.transaction_type) }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap font-medium">
                                            {{ formatCurrency(transaction.amount) }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            {{ getBalanceTypeLabel(transaction.balance_type) }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(transaction.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="latestTransactions.length === 0">
                                        <td colspan="5" class="px-3 py-4 text-center text-gray-500">
                                            No hay transacciones recientes
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button variant="outline" size="sm" @click="$inertia.visit(route('admin.transactions.index'))">
                            Ver todas las transacciones
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 