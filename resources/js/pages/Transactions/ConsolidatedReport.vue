<script setup lang="ts">
import { ref } from 'vue';
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
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { onMounted } from 'vue';

interface Dataset {
    label: string;
    data: number[];
    backgroundColor: string;
    borderColor: string;
}

interface ChartData {
    labels: string[];
    datasets: Dataset[];
}

interface Props {
    chartData: ChartData;
    totals: {
        capital: number;
        earnings: number;
        network: number;
        all: number;
    };
    filters: {
        date_from: string;
        date_to: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: route('dashboard') },
    { label: 'Transacciones', href: route('transactions.index') },
    { label: 'Reporte Consolidado', href: route('transactions.report') }
];

// Filtros
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

function updateFilters() {
    router.get(route('transactions.report'), {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

// Chart.js
let chart = null;

onMounted(() => {
    // Usar setTimeout para asegurarse de que el DOM está completamente cargado
    setTimeout(() => {
        renderChart();
    }, 100);
});

async function renderChart() {
    // Verificar si hay datos para mostrar
    if (!props.chartData.labels || props.chartData.labels.length === 0) {
        return; // No hay datos para mostrar, salir de la función
    }

    // Verificar que el elemento canvas exista
    const canvasElement = document.getElementById('transactionsChart');
    if (!canvasElement) {
        console.error('El elemento canvas no fue encontrado');
        return;
    }

    // Obtener el contexto
    const ctx = canvasElement.getContext('2d');
    if (!ctx) {
        console.error('No se pudo obtener el contexto del canvas');
        return;
    }
    
    // Importar dinámicamente Chart.js para evitar problemas con SSR
    const { Chart, LineController, CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend } = await import('chart.js');
    
    // Registrar los componentes necesarios
    Chart.register(LineController, CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend);
    
    // Destruir el gráfico existente si hay uno
    if (chart !== null) {
        chart.destroy();
    }
    
    // Crear el nuevo gráfico
    chart = new Chart(ctx, {
        type: 'line',
        data: props.chartData,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(value);
                        }
                    }
                }
            }
        }
    });
}

// Formatear fechas
function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
</script>

<template>
    <Head title="Reporte Consolidado" />

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

                <h1 class="text-2xl font-semibold text-gray-900 mb-6">Reporte Consolidado de Transacciones</h1>
                
                <!-- Tarjetas de resumen -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Total</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.all) }}</div>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button @click="updateFilters">Aplicar Filtros</Button>
                    </CardFooter>
                </Card>

                <!-- Gráfico de transacciones -->
                <Card>
                    <CardHeader>
                        <CardTitle>Movimientos por Día</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.chartData.labels.length === 0" class="text-center py-8 text-gray-500">
                            No hay datos para mostrar en el período seleccionado.
                        </div>
                        <div v-else class="h-64 md:h-80">
                            <canvas id="transactionsChart"></canvas>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <p class="text-sm text-gray-500">
                            Período: {{ formatDate(props.filters.date_from) }} - {{ formatDate(props.filters.date_to) }}
                        </p>
                        <div>
                            <Link :href="route('transactions.index')" class="text-indigo-600 hover:text-indigo-900">
                                Volver a transacciones
                            </Link>
                        </div>
                    </CardFooter>
                </Card>

                <!-- Resumen por tipo -->
                <Card class="mt-6">
                    <CardHeader>
                        <CardTitle>Resumen por Tipo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-3 w-3 rounded-full bg-green-500 mr-2"></div>
                                                Capital
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.capital) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ props.totals.all ? Math.round((props.totals.capital / props.totals.all) * 100) : 0 }}%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-3 w-3 rounded-full bg-blue-500 mr-2"></div>
                                                Ganancias
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.earnings) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ props.totals.all ? Math.round((props.totals.earnings / props.totals.all) * 100) : 0 }}%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-3 w-3 rounded-full bg-purple-500 mr-2"></div>
                                                Comisiones de Red
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.totals.network) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ props.totals.all ? Math.round((props.totals.network / props.totals.all) * 100) : 0 }}%
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