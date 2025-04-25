<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { LineChart, PieChart, BarChart } from '@/components/ui/charts';
import { type BreadcrumbItem } from '@/types';
import { 
    DollarSign, 
    Users, 
    CreditCard,
    CheckCircle,
    XCircle,
    Clock,
    TrendingUp,
    Calendar,
    Filter
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface MembershipStats {
    id: number;
    nombre: string;
    precio: number;
    total_payments: number;
    total_amount: number;
    approved_count: number;
    pending_count: number;
    rejected_count: number;
}

interface Payment {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    membresia: {
        id: number;
        nombre: string;
        precio: number;
    };
    amount: number;
    status: string;
    created_at: string;
    processed_at: string | null;
}

interface Props {
    filters: {
        startDate: string;
        endDate: string;
    };
    stats: {
        totalPayments: number;
        totalAmount: number;
        pendingPayments: number;
        approvedPayments: number;
        rejectedPayments: number;
    };
    membershipStats: MembershipStats[];
    recentPayments: Payment[];
    chartData: {
        membershipDistribution: {
            labels: string[];
            data: number[];
        };
        dailyTrend: {
            labels: string[];
            payments: number[];
            amounts: number[];
        }
    };
}

const props = defineProps<Props>();

const startDate = ref(props.filters.startDate);
const endDate = ref(props.filters.endDate);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administración',
        href: '/admin',
    },
    {
        title: 'Membresías',
        href: route('admin.memberships.index'),
    },
    {
        title: 'Estadísticas',
        href: route('admin.memberships.stats'),
    }
];

// Procesar datos para gráficos
const membershipDistributionData = {
    labels: props.chartData.membershipDistribution.labels,
    datasets: [
        {
            data: props.chartData.membershipDistribution.data,
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

const dailyTrendData = {
    labels: props.chartData.dailyTrend.labels,
    datasets: [
        {
            label: 'Cantidad de pagos',
            data: props.chartData.dailyTrend.payments,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            tension: 0.4
        },
        {
            label: 'Monto total $',
            data: props.chartData.dailyTrend.amounts,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            tension: 0.4,
            yAxisID: 'y1'
        }
    ]
};

const lineChartOptions = {
    responsive: true,
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: 'Cantidad'
            }
        },
        y1: {
            position: 'right',
            beginAtZero: true,
            title: {
                display: true,
                text: 'Monto ($)'
            },
            grid: {
                drawOnChartArea: false
            }
        },
        x: {
            title: {
                display: true,
                text: 'Fecha'
            }
        }
    }
};

// Formatear moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(value);
};

// Formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return format(new Date(dateString), 'dd/MM/yyyy HH:mm', { locale: es });
};

// Obtener clase de estado para colorear
const getStatusClass = (status) => {
    switch (status) {
        case 'aprobado': return 'text-green-500';
        case 'pendiente': return 'text-amber-500';
        case 'rechazado': return 'text-red-500';
        default: return 'text-gray-500';
    }
};

// Manejar cambio de fechas
const updateDateRange = () => {
    router.get(route('admin.memberships.stats'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, {
        preserveState: true,
        replace: true
    });
};
</script>

<template>
    <Head title="Estadísticas de Membresías" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Estadísticas de Membresías</h1>
                    <p class="text-gray-600">Análisis y tendencias de pagos de membresías</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <Calendar class="h-4 w-4 text-gray-500" />
                        <Input 
                            type="date" 
                            v-model="startDate" 
                            class="w-40" 
                        />
                    </div>
                    <span>hasta</span>
                    <div class="flex items-center space-x-2">
                        <Calendar class="h-4 w-4 text-gray-500" />
                        <Input 
                            type="date" 
                            v-model="endDate" 
                            class="w-40" 
                        />
                    </div>
                    <Button @click="updateDateRange">
                        <Filter class="h-4 w-4 mr-2" />
                        Filtrar
                    </Button>
                </div>
            </div>
            
            <!-- Tarjetas de resumen -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Total Pagos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center">
                            <CreditCard class="h-8 w-8 text-primary mr-2" />
                            <div class="text-2xl font-bold">{{ props.stats.totalPayments }}</div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Monto Total</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center">
                            <DollarSign class="h-8 w-8 text-green-500 mr-2" />
                            <div class="text-2xl font-bold">{{ formatCurrency(props.stats.totalAmount) }}</div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Aprobados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center">
                            <CheckCircle class="h-8 w-8 text-green-500 mr-2" />
                            <div class="text-2xl font-bold">{{ props.stats.approvedPayments }}</div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Pendientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center">
                            <Clock class="h-8 w-8 text-amber-500 mr-2" />
                            <div class="text-2xl font-bold">{{ props.stats.pendingPayments }}</div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-gray-500">Rechazados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center">
                            <XCircle class="h-8 w-8 text-red-500 mr-2" />
                            <div class="text-2xl font-bold">{{ props.stats.rejectedPayments }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Distribución por Membresía</CardTitle>
                        <CardDescription>Cantidad de pagos por tipo de membresía</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="h-80">
                            <PieChart 
                                :chartData="membershipDistributionData"
                            />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader>
                        <CardTitle>Tendencia Diaria</CardTitle>
                        <CardDescription>Pagos y montos por día</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="h-80">
                            <LineChart 
                                :chartData="dailyTrendData"
                                :options="lineChartOptions"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <!-- Tabla de estadísticas por membresía -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Estadísticas por Membresía</CardTitle>
                    <CardDescription>Desglose de pagos por tipo de membresía</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-3 px-4 text-left">Membresía</th>
                                    <th class="py-3 px-4 text-left">Precio</th>
                                    <th class="py-3 px-4 text-center">Total Pagos</th>
                                    <th class="py-3 px-4 text-right">Monto Total</th>
                                    <th class="py-3 px-4 text-center">Aprobados</th>
                                    <th class="py-3 px-4 text-center">Pendientes</th>
                                    <th class="py-3 px-4 text-center">Rechazados</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="props.membershipStats.length === 0">
                                    <td colspan="7" class="py-4 px-4 text-center text-gray-500">
                                        No hay datos disponibles
                                    </td>
                                </tr>
                                <tr v-for="stat in props.membershipStats" :key="stat.id" class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ stat.nombre }}</td>
                                    <td class="py-3 px-4">{{ formatCurrency(stat.precio) }}</td>
                                    <td class="py-3 px-4 text-center">{{ stat.total_payments }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(stat.total_amount) }}</td>
                                    <td class="py-3 px-4 text-center text-green-500">{{ stat.approved_count }}</td>
                                    <td class="py-3 px-4 text-center text-amber-500">{{ stat.pending_count }}</td>
                                    <td class="py-3 px-4 text-center text-red-500">{{ stat.rejected_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
            
            <!-- Pagos recientes -->
            <Card>
                <CardHeader>
                    <CardTitle>Pagos Recientes</CardTitle>
                    <CardDescription>
                        Últimos 10 pagos de membresías
                        <Link :href="route('admin.memberships.payments')" class="text-primary hover:underline ml-2">
                            Ver todos los pagos
                        </Link>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-3 px-4 text-left">Usuario</th>
                                    <th class="py-3 px-4 text-left">Membresía</th>
                                    <th class="py-3 px-4 text-right">Monto</th>
                                    <th class="py-3 px-4 text-center">Estado</th>
                                    <th class="py-3 px-4 text-center">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="props.recentPayments.length === 0">
                                    <td colspan="5" class="py-4 px-4 text-center text-gray-500">
                                        No hay pagos recientes
                                    </td>
                                </tr>
                                <tr v-for="payment in props.recentPayments" :key="payment.id" class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <Link :href="route('admin.users.show', { user: payment.user.id })" class="text-primary hover:underline">
                                            {{ payment.user.name }}
                                        </Link>
                                        <div class="text-sm text-gray-500">
                                            {{ payment.user.email }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div>{{ payment.membresia.nombre }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ formatCurrency(payment.membresia.precio) }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span :class="getStatusClass(payment.status)">
                                            {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div>{{ formatDate(payment.created_at) }}</div>
                                        <div v-if="payment.processed_at" class="text-sm text-gray-500">
                                            Procesado: {{ formatDate(payment.processed_at) }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 