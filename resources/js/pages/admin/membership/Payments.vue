<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { 
    DollarSign, 
    Search, 
    Filter,
    CheckCircle,
    XCircle,
    Clock,
    Image,
    Eye
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface Membresia {
    id: number;
    nombre: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Payment {
    id: number;
    user: User;
    membresia: {
        id: number;
        nombre: string;
        precio: number;
    };
    amount: number;
    bank_name: string;
    transaction_reference: string;
    receipt_image: string | null;
    notes: string | null;
    status: string;
    created_at: string;
    processed_at: string | null;
}

interface Props {
    filters: {
        search?: string;
        status?: string;
        membership_id?: number;
        date_from?: string;
        date_to?: string;
    };
    membresias: Membresia[];
    payments: {
        data: Payment[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
}

const props = defineProps<Props>();

// Configuración de filtros
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const membershipId = ref(props.filters.membership_id || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Modal para ver comprobante
const showReceiptModal = ref(false);
const selectedReceipt = ref<string | null>(null);
const selectedPayment = ref<Payment | null>(null);

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
        title: 'Pagos',
        href: route('admin.memberships.payments'),
    }
];

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

// Aplicar filtros
const applyFilters = () => {
    router.get(route('admin.memberships.payments'), {
        search: search.value || undefined,
        status: status.value || undefined,
        membership_id: membershipId.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        replace: true
    });
};

// Resetear filtros
const resetFilters = () => {
    search.value = '';
    status.value = '';
    membershipId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

// Ver comprobante
const viewReceipt = (payment: Payment) => {
    if (payment.receipt_image) {
        selectedReceipt.value = payment.receipt_image;
        selectedPayment.value = payment;
        showReceiptModal.value = true;
    }
};

// Debounce para búsqueda
let searchTimer: any = null;
watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 500);
});
</script>

<template>
    <Head title="Pagos de Membresías" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Pagos de Membresías</h1>
                    <p class="text-gray-600">Gestión y seguimiento de pagos de membresías</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <Link :href="route('admin.memberships.stats')" class="inline-flex">
                        <Button>
                            Ver Estadísticas
                        </Button>
                    </Link>
                </div>
            </div>
            
            <!-- Filtros -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="text-lg">Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Buscar</label>
                            <div class="relative">
                                <Search class="absolute left-2 top-2.5 h-4 w-4 text-gray-500" />
                                <Input
                                    v-model="search"
                                    placeholder="Nombre o email"
                                    class="pl-8"
                                />
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium mb-1 block">Estado</label>
                            <Select v-model="status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todos</SelectItem>
                                    <SelectItem value="pendiente">Pendientes</SelectItem>
                                    <SelectItem value="aprobado">Aprobados</SelectItem>
                                    <SelectItem value="rechazado">Rechazados</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium mb-1 block">Membresía</label>
                            <Select v-model="membershipId">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Todas</SelectItem>
                                    <SelectItem v-for="membresia in props.membresias" :key="membresia.id" :value="membresia.id.toString()">
                                        {{ membresia.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium mb-1 block">Desde</label>
                            <Input v-model="dateFrom" type="date" />
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium mb-1 block">Hasta</label>
                            <Input v-model="dateTo" type="date" />
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-4 space-x-2">
                        <Button variant="outline" @click="resetFilters">
                            Limpiar
                        </Button>
                        <Button @click="applyFilters">
                            <Filter class="h-4 w-4 mr-2" />
                            Aplicar Filtros
                        </Button>
                    </div>
                </CardContent>
            </Card>
            
            <!-- Tabla de pagos -->
            <Card>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="py-3 px-4 text-left font-medium text-sm text-gray-500">Usuario</th>
                                    <th class="py-3 px-4 text-left font-medium text-sm text-gray-500">Membresía</th>
                                    <th class="py-3 px-4 text-right font-medium text-sm text-gray-500">Monto</th>
                                    <th class="py-3 px-4 text-left font-medium text-sm text-gray-500">Banco</th>
                                    <th class="py-3 px-4 text-center font-medium text-sm text-gray-500">Estado</th>
                                    <th class="py-3 px-4 text-center font-medium text-sm text-gray-500">Fecha</th>
                                    <th class="py-3 px-4 text-center font-medium text-sm text-gray-500">Comprobante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="props.payments.data.length === 0">
                                    <td colspan="7" class="py-4 px-4 text-center text-gray-500">
                                        No se encontraron pagos con los filtros seleccionados
                                    </td>
                                </tr>
                                <tr v-for="payment in props.payments.data" :key="payment.id" class="border-b hover:bg-gray-50">
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
                                    <td class="py-3 px-4">
                                        <div>{{ payment.bank_name }}</div>
                                        <div class="text-sm text-gray-500 truncate max-w-32" :title="payment.transaction_reference">
                                            Ref: {{ payment.transaction_reference }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span :class="getStatusClass(payment.status)" class="inline-flex items-center">
                                            <template v-if="payment.status === 'aprobado'">
                                                <CheckCircle class="h-4 w-4 mr-1" />
                                            </template>
                                            <template v-else-if="payment.status === 'pendiente'">
                                                <Clock class="h-4 w-4 mr-1" />
                                            </template>
                                            <template v-else-if="payment.status === 'rechazado'">
                                                <XCircle class="h-4 w-4 mr-1" />
                                            </template>
                                            {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div>{{ formatDate(payment.created_at) }}</div>
                                        <div v-if="payment.processed_at" class="text-sm text-gray-500">
                                            Procesado: {{ formatDate(payment.processed_at) }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <Button variant="ghost" size="sm" @click="viewReceipt(payment)" v-if="payment.receipt_image">
                                            <Image class="h-4 w-4 mr-1" />
                                            <span class="sr-only">Ver comprobante</span>
                                        </Button>
                                        <span v-else class="text-gray-400 text-sm">No disponible</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <div class="p-4 border-t">
                        <Pagination :links="props.payments.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
        
        <!-- Modal para ver comprobante -->
        <Dialog :open="showReceiptModal" @update:open="showReceiptModal = $event">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Comprobante de Pago</DialogTitle>
                    <DialogDescription v-if="selectedPayment">
                        {{ selectedPayment.user.name }} - {{ selectedPayment.membresia.nombre }} - 
                        {{ formatCurrency(selectedPayment.amount) }}
                    </DialogDescription>
                </DialogHeader>
                
                <div class="mt-2 relative">
                    <img 
                        v-if="selectedReceipt" 
                        :src="'/storage/' + selectedReceipt" 
                        alt="Comprobante de pago" 
                        class="max-h-[70vh] mx-auto object-contain"
                    />
                    <div v-else class="p-8 text-center text-gray-500">
                        No se pudo cargar la imagen
                    </div>
                </div>
                
                <DialogFooter>
                    <Button @click="showReceiptModal = false">Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template> 