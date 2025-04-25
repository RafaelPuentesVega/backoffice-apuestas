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

interface Commission {
    id: number;
    user_id: number;
    sponsor_id: number;
    membresia_id: number;
    monto: number;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    membresia: {
        id: number;
        nombre: string;
    };
}

interface Referral {
    id: number;
    name: string;
    email: string;
}

interface Stats {
    total_commissions: number;
    total_referrals: number;
    active_referrals: number;
    commissions_by_date: Record<string, number>;
}

interface Props {
    commissions: Commission[];
    stats: Stats;
    referrals: Referral[];
    filters: {
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: route('dashboard') },
    { label: 'Transacciones', href: route('transactions.index') },
    { label: 'Comisiones de Red', href: route('transactions.network') }
];

// Filtros
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

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
    router.get(route('transactions.network'), {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

const commissionsGroupedByDate = computed(() => {
    const grouped = {};
    props.commissions.forEach(commission => {
        const date = commission.created_at.split('T')[0];
        if (!grouped[date]) {
            grouped[date] = [];
        }
        grouped[date].push(commission);
    });
    return grouped;
});
</script>

<template>
    <Head title="Comisiones de Red" />

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

                <h1 class="text-2xl font-semibold text-gray-900 mb-6">Comisiones de Red</h1>

                <!-- Tarjetas de resumen -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Total Comisiones</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.stats.total_commissions) }}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Total Referidos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.stats.total_referrals }}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Referidos Activos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.stats.active_referrals }}</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm text-gray-500">Promedio por Referido</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{ props.stats.active_referrals 
                                    ? new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(props.stats.total_commissions / props.stats.active_referrals) 
                                    : '$0.00' }}
                            </div>
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

                <!-- Lista de comisiones -->
                <Card>
                    <CardHeader>
                        <CardTitle>Historial de Comisiones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.commissions.length === 0" class="text-center py-4 text-gray-500">
                            No hay comisiones registradas en el período seleccionado.
                        </div>
                        
                        <div v-else class="space-y-6">
                            <div v-for="(commissions, date) in commissionsGroupedByDate" :key="date" class="border-b border-gray-200 pb-4">
                                <h3 class="font-medium text-gray-900 mb-2">{{ new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' }) }}</h3>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hora</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referido</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="commission in commissions" :key="commission.id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ new Date(commission.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ commission.user.name }}
                                                    <div class="text-xs text-gray-500">{{ commission.user.email }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ commission.membresia.nombre }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-green-600">
                                                    {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(commission.monto) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Lista de referidos -->
                <Card class="mt-6">
                    <CardHeader>
                        <CardTitle>Mis Referidos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.referrals.length === 0" class="text-center py-4 text-gray-500">
                            No tienes referidos registrados.
                        </div>
                        
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="referral in props.referrals" :key="referral.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ referral.id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ referral.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ referral.email }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <div>
                            <Link :href="route('transactions.index')" class="text-indigo-600 hover:text-indigo-900">
                                Volver a transacciones
                            </Link>
                        </div>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 