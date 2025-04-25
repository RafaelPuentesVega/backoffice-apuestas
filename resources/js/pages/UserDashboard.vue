<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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
import { Badge } from '@/components/ui/badge';
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table';
import { Toast } from '@/components/ui/toast';
import {
  ArrowUp,
  ArrowDown,
  Users,
  Clock,
  DollarSign,
  Wallet,
  ChevronRight,
  PieChart,
  BarChart3,
} from 'lucide-vue-next';

// Importar Chart.js
import { Chart } from 'chart.js/auto';

// Definición de interfaces
interface Transaction {
  id: number;
  amount: number;
  type: string;
  status: string;
  date: string;
  description: string;
  reference: string;
}

interface BalanceHistory {
  date: string;
  capital: number;
  earnings: number;
  network: number;
}

interface ReferralData {
  id: number;
  name: string;
  email: string;
  date: string;
  status: string;
}

interface Props {
  user: {
    id: number;
    name: string;
    email: string;
    capital_balance: number;
    earnings_balance: number;
    network_balance: number;
    membership: {
      id: number;
      nombre: string;
      precio: number;
      comision_directa: number;
      porcentaje_rendimiento: number;
    } | null;
    membership_expires_at: string | null;
    code_referral: string;
    pending_membership: {
      id: number;
      nombre: string;
      precio: number;
      comision_directa: number;
      porcentaje_rendimiento: number;
    } | null;
  };
  balanceHistory: BalanceHistory[];
  recentTransactions: Transaction[];
  referrals: ReferralData[];
  pendingActions: {
    deposits: number;
    withdrawals: number;
  };
  depositLimit: {
    min: number;
    max: number;
  };
  withdrawalLimit: {
    min: number;
    max: number;
  };
}

const props = defineProps<Props>();

// Calcular balance total
const totalBalance = computed(() => {
  return Number(props.user.capital_balance || 0) +
    Number(props.user.earnings_balance || 0) +
    Number(props.user.network_balance || 0);
});

// Gráficos y configuración
onMounted(() => {
  initializeCharts();
});

// Función para inicializar los gráficos
function initializeCharts(): void {
  // Gráfico de historial de balance
  const historyCtx = document.getElementById('balance-history-chart') as HTMLCanvasElement;
  if (historyCtx) {
    new Chart(historyCtx, {
      type: 'line',
      data: {
        labels: props.balanceHistory.map((item) => item.date),
        datasets: [
          {
            label: 'Capital',
            data: props.balanceHistory.map((item) => item.capital),
            borderColor: 'rgb(22, 163, 74)',
            backgroundColor: 'rgba(22, 163, 74, 0.1)',
            tension: 0.3,
          },
          {
            label: 'Ganancias',
            data: props.balanceHistory.map((item) => item.earnings),
            borderColor: 'rgb(37, 99, 235)',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.3,
          },
          {
            label: 'Red',
            data: props.balanceHistory.map((item) => item.network),
            borderColor: 'rgb(147, 51, 234)',
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            tension: 0.3,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return new Intl.NumberFormat('es-CO', {
                  style: 'currency',
                  currency: 'USD',
                  notation: 'compact',
                  maximumFractionDigits: 1
                }).format(Number(value));
              }
            }
          }
        },
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed.y !== null) {
                  label += formatCurrency(context.parsed.y);
                }
                return label;
              }
            }
          }
        }
      }
    });
  }

  // Gráfico de distribución de balance
  const distributionCtx = document.getElementById('balance-distribution-chart') as HTMLCanvasElement;
  if (distributionCtx) {
    new Chart(distributionCtx, {
      type: 'doughnut',
      data: {
        labels: ['Capital', 'Ganancias', 'Red'],
        datasets: [{
          data: [
            props.user.capital_balance,
            props.user.earnings_balance,
            props.user.network_balance
          ],
          backgroundColor: [
            'rgb(22, 163, 74)',
            'rgb(37, 99, 235)',
            'rgb(147, 51, 234)'
          ],
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.raw as number;
                const total = (context.dataset.data as number[]).reduce((a, b) => Number(a) + Number(b), 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${formatCurrency(value)} (${percentage}%)`;
              }
            }
          },
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }
}

// Formatear montos a formato de moneda
const formatCurrency = (amount: number): string => {
  // Prevenir NaN
  if (isNaN(amount) || amount === null || amount === undefined) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'USD',
    }).format(0);
  }
  
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

// Método para formatear fecha y hora
const formatDate = (dateString: string | null): string => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('es-CO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
    timeZone: 'America/Bogota',
  });
};

// Método para obtener etiqueta legible de tipo de transacción
const getTransactionTypeLabel = (type: string): string => {
  const types: Record<string, string> = {
    deposit: 'Depósito',
    withdrawal: 'Retiro',
    earnings: 'Ganancia',
    commission: 'Comisión',
    transfer: 'Transferencia',
    bonus: 'Bono',
    fee: 'Comisión',
    membership: 'Membresía',
    membership_payment: 'Pago de Membresía',
    membership_renewal: 'Renovación de Membresía',
    referral_commission: 'Comisión de Referido',
    network_commission: 'Comisión de Red',
  };
  return types[type] || type;
};

// Método para obtener etiqueta legible de estado
const getStatusLabel = (status: string): string => {
  const statuses: Record<string, string> = {
    pending: 'Pendiente',
    pendiente: 'Pendiente',
    approved: 'Aprobado',
    aprobado: 'Aprobado',
    rejected: 'Rechazado',
    rechazado: 'Rechazado',
    completed: 'Completado',
    completado: 'Completado',
    cancelled: 'Cancelado',
    cancelado: 'Cancelado',
    processing: 'Procesando',
    procesando: 'Procesando',
    success: 'Exitoso',
    failed: 'Fallido',
  };
  return statuses[status.toLowerCase()] || status;
};

// Método para obtener color de badge según estado
const getStatusColor = (status: string): string => {
  const lowercaseStatus = status.toLowerCase();
  const colors: Record<string, string> = {
    pending: 'bg-yellow-500',
    pendiente: 'bg-yellow-500',
    approved: 'bg-green-500',
    aprobado: 'bg-green-500',
    rejected: 'bg-red-500',
    rechazado: 'bg-red-500',
    completed: 'bg-green-500',
    completado: 'bg-green-500',
    cancelled: 'bg-gray-500',
    cancelado: 'bg-gray-500',
    processing: 'bg-blue-500',
    procesando: 'bg-blue-500',
    success: 'bg-green-500',
    failed: 'bg-red-500',
  };
  return colors[lowercaseStatus] || 'bg-gray-500';
};

// Migas de pan para la navegación
const breadcrumbs = [
  { title: 'Inicio', href: route('dashboard') },
  { title: 'Mi Dashboard', href: route('user.dashboard'), current: true },
];

function getDaysRemaining(): number {
  if (!props.user.membership_expires_at) return 0;
  
  const expirationDate = new Date(props.user.membership_expires_at);
  const now = new Date();
  
  // Return 0 if already expired
  if (expirationDate <= now) return 0;
  
  // Calculate days difference
  const diffTime = expirationDate.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return diffDays;
}

function isExpired(): boolean {
  if (!props.user.membership_expires_at) return false;
  
  const expirationDate = new Date(props.user.membership_expires_at);
  const now = new Date();
  
  return expirationDate <= now;
}

// Función para obtener la URL base de la aplicación
const getBaseUrl = () => {
  return window.location.origin;
};

// Función para generar el enlace de referido completo
const getReferralLink = () => {
  return `${getBaseUrl()}/register?sponsor=${props.user.code_referral}`;
};

// Función para copiar al portapapeles
const copyToClipboard = (text: string) => {
  if (navigator && navigator.clipboard) {
    navigator.clipboard.writeText(text)
      .then(() => {
        Toast.success({ title: 'Código copiado al portapapeles' });
      })
      .catch(err => {
        console.error('Error al copiar texto: ', err);
      });
  }
};

// Para copiar código de referido y enlace
const copyReferralLink = () => {
  copyToClipboard(getReferralLink());
};
</script>

<template>
    <Head title="Dashboard de Usuario" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <!-- Mensaje de bienvenida y resumen -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-2">Bienvenido, {{ user.name }}</h1>
                <p class="text-gray-600">
                    Aquí podrás gestionar tus balances, transacciones y referidos. Tu balance total es 
                    <span class="font-bold text-primary">{{ formatCurrency(totalBalance) }}</span>.
                </p>
            </div>

            <!-- User info section -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- User info section -->
                <div class="col-span-1 lg:col-span-12 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Código de referido: <span class="font-semibold">{{ user.code_referral }}</span>
                            </p>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-col">
                                <!-- Membresía pendiente - Mostrar primero si existe -->
                                <div v-if="user.pending_membership" class="mb-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p class="font-semibold text-yellow-800 mb-1">Membresía Pendiente de Aprobación</p>
                                    <p class="text-sm text-gray-700">
                                        {{ user.pending_membership.nombre }} - {{ formatCurrency(user.pending_membership.precio) }}
                                    </p>
                                    <div class="mt-1 flex items-center gap-1">
                                        <span class="inline-block h-2 w-2 rounded-full bg-yellow-500"></span>
                                        <span class="text-xs text-yellow-700">En espera de confirmación</span>
                                    </div>
                                </div>
                                
                                <!-- Membresía actual -->
                                <span class="text-sm text-gray-500 dark:text-gray-400">Membresía actual:</span>
                                <span v-if="user.membership" class="font-semibold text-gray-900 dark:text-white">{{ user.membership.nombre }}</span>
                                <span v-else class="font-semibold text-gray-500 dark:text-gray-400">Sin membresía</span>
                                
                                <div v-if="user.membership && user.membership_expires_at" class="mt-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Expira en:</span>
                                    <span v-if="getDaysRemaining() > 30" class="text-green-600 dark:text-green-400">
                                        {{ getDaysRemaining() }} días ({{ formatDate(user.membership_expires_at) }})
                                    </span>
                                    <span v-else-if="getDaysRemaining() <= 30 && getDaysRemaining() > 7" class="text-yellow-600 dark:text-yellow-400">
                                        {{ getDaysRemaining() }} días ({{ formatDate(user.membership_expires_at) }})
                                    </span>
                                    <span v-else class="text-red-600 dark:text-red-400">
                                        {{ getDaysRemaining() }} días ({{ formatDate(user.membership_expires_at) }})
                                    </span>
                                </div>
                                
                                <div v-if="isExpired()" class="mt-1 text-red-600 dark:text-red-400 font-semibold">
                                    ¡Membresía expirada! Por favor renueve su membresía.
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="/membership/select" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium" 
                               v-if="!user.pending_membership">
                                {{ user.membership ? 'Renovar Membresía' : 'Obtener Membresía' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de balance -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <DollarSign class="h-5 w-5 mr-2 text-green-600" />
                            Balance de Capital
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold mb-1">{{ formatCurrency(user.capital_balance) }}</div>
                        <p class="text-sm text-gray-500">
                            Tus fondos disponibles para apuestas
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Wallet class="h-5 w-5 mr-2 text-blue-600" />
                            Balance de Ganancias
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold mb-1">{{ formatCurrency(user.earnings_balance) }}</div>
                        <p class="text-sm text-gray-500">
                            Beneficios obtenidos con tus apuestas
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Users class="h-5 w-5 mr-2 text-purple-600" />
                            Balance de Red
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold mb-1">{{ formatCurrency(user.network_balance) }}</div>
                        <p class="text-sm text-gray-500">
                            Comisiones por referidos
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Acciones rápidas y notificaciones -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Acciones rápidas -->
                <Card>
                    <CardHeader>
                        <CardTitle>Acciones Rápidas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <Link href="/deposit">
                                    <Button class="w-full mb-2 gap-2">
                                        <ArrowUp class="h-4 w-4" />
                                        Realizar Depósito
                                    </Button>
                                </Link>
 
                            </div>
                            <div>
                                <Link href="/withdrawal">
                                    <Button class="w-full mb-2 gap-2" variant="outline">
                                        <ArrowDown class="h-4 w-4" />
                                        Solicitar Retiro
                                    </Button>
                                </Link>

                            </div>
                            <div>
                                <Link href="/network">
                                    <Button class="w-full gap-2" variant="secondary">
                                        <Users class="h-4 w-4" />
                                        Invitar Referidos
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notificaciones de acciones pendientes -->
                <Card v-if="pendingActions.deposits > 0 || pendingActions.withdrawals > 0 || user.pending_membership">
                    <CardHeader>
                        <CardTitle>Acciones Pendientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-if="user.pending_membership" class="flex items-center p-3 bg-yellow-50 rounded-lg">
                                <Clock class="h-5 w-5 text-yellow-600 mr-2" />
                                <div>
                                    <p class="font-medium">Membresía pendiente de aprobación</p>
                                    <p class="text-sm text-gray-600">{{ user.pending_membership.nombre }}</p>
                                </div>
                            </div>
                            <div v-if="pendingActions.deposits > 0" class="flex items-center p-3 bg-yellow-50 rounded-lg">
                                <Clock class="h-5 w-5 text-yellow-600 mr-2" />
                                <div>
                                    <p class="font-medium">{{ pendingActions.deposits }} depósito(s) pendiente(s)</p>
                                    <p class="text-sm text-gray-600">En espera de aprobación</p>
                                </div>
                            </div>
                            <div v-if="pendingActions.withdrawals > 0" class="flex items-center p-3 bg-yellow-50 rounded-lg">
                                <Clock class="h-5 w-5 text-yellow-600 mr-2" />
                                <div>
                                    <p class="font-medium">{{ pendingActions.withdrawals }} retiro(s) pendiente(s)</p>
                                    <p class="text-sm text-gray-600">En proceso de verificación</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Código de referido -->
                <Card>
                    <CardHeader>
                        <CardTitle>Refiere</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="p-3 bg-gray-50 rounded-lg mb-4">
                            <p class="text-sm text-gray-600 mb-1">Enlace de invitación:</p>
                            <div class="flex items-center">
                                <code class="bg-gray-200 p-2 rounded font-mono text-sm flex-1 overflow-auto text-xs md:text-sm">{{ getReferralLink() }}</code>
                                <Button variant="ghost" size="sm" class="ml-2" @click="copyReferralLink">
                                    Copiar
                                </Button>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500">
                            Comparte este enlace con tus amigos y gana comisiones por cada apuesta que realicen.
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Link href="/network" class="text-sm text-primary flex items-center">
                            Ver programa de referidos
                            <ChevronRight class="h-4 w-4 ml-1" />
                        </Link>
                    </CardFooter>
                </Card>
            </div>

            <!-- Gráficos y estadísticas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Gráfico de historial de balance -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <BarChart3 class="h-5 w-5 mr-2" />
                            Historial de Balance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="balance-history-chart" width="400" height="200"></canvas>
                        </div>
                    </CardContent>
                </Card>

                <!-- Gráfico de distribución de balance -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <PieChart class="h-5 w-5 mr-2" />
                            Distribución de Balance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="balance-distribution-chart" width="400" height="200"></canvas>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Transacciones recientes -->
            <Card class="mb-6">
                <CardHeader class="flex justify-between items-start">
                    <div>
                        <CardTitle>Transacciones Recientes</CardTitle>
                        <CardDescription>Últimas 5 transacciones realizadas</CardDescription>
                    </div>
                    <Link href="/transactions">
                        <Button variant="ghost" size="sm" class="flex items-center">
                            Ver todas
                            <ChevronRight class="h-4 w-4 ml-1" />
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Monto</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead class="text-right">Referencia</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="transaction in recentTransactions" :key="transaction.id">
                                <TableCell>{{ formatDate(transaction.date) }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center">
                                        <span class="font-medium">{{ getTransactionTypeLabel(transaction.type) }}</span>
                                    </div>
                                </TableCell>
                                <TableCell :class="transaction.amount > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatCurrency(transaction.amount) }}
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getStatusColor(transaction.status) + ' text-white'">
                                        {{ getStatusLabel(transaction.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right font-mono text-xs truncate max-w-[120px]" :title="transaction.reference">
                                    {{ transaction.reference }}
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!recentTransactions || recentTransactions.length === 0">
                                <TableCell colspan="5" class="text-center py-4 text-gray-500">
                                    No hay transacciones recientes
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Referidos -->
            <Card>
                <CardHeader class="flex justify-between items-start">
                    <div>
                        <CardTitle>Mis Referidos</CardTitle>
                        <CardDescription>Usuarios que se han registrado con tu código</CardDescription>
                    </div>
                    <Link href="/referral">
                        <Button variant="ghost" size="sm" class="flex items-center">
                            Ver todos
                            <ChevronRight class="h-4 w-4 ml-1" />
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Fecha de registro</TableHead>
                                <TableHead>Estado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="referral in referrals" :key="referral.id">
                                <TableCell>{{ referral.name }}</TableCell>
                                <TableCell>{{ referral.email }}</TableCell>
                                <TableCell>{{ formatDate(referral.date) }}</TableCell>
                                <TableCell>
                                    <Badge :class="getStatusColor(referral.status) + ' text-white'">
                                        {{ getStatusLabel(referral.status) }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!referrals || referrals.length === 0">
                                <TableCell colspan="4" class="text-center py-4 text-gray-500">
                                    No tienes referidos registrados aún
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
                <CardFooter>
                    <p class="text-sm text-gray-500">
                        Invita a más amigos para aumentar tus comisiones por red
                    </p>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template> 