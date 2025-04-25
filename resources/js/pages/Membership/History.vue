<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Eye } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

interface MembershipHistory {
    id: number;
    membresia: {
        id: number;
        nombre: string;
        precio: number;
    };
    precio_pagado: number;
    status: string;
    payment_method: string;
    payment_reference: string;
    receipt_image: string | null;
    activated_at: string;
    expires_at: string | null;
    canceled_at: string | null;
}

interface Props {
    membership_history: MembershipHistory[];
}

defineProps<Props>();

const showReceiptDialog = ref(false);
const selectedMembershipId = ref<number | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Historial de Membresías',
        href: '/membership/history',
    }
];

// Formatear montos a moneda
const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
};

// Formatear fechas
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Obtener texto y color para el estado
const getStatusDetails = (status: string): { text: string, color: string } => {
    switch (status) {
        case 'activa':
            return { text: 'Activa', color: 'bg-green-100 text-green-800' };
        case 'expirada':
            return { text: 'Expirada', color: 'bg-yellow-100 text-yellow-800' };
        case 'cancelada':
            return { text: 'Cancelada', color: 'bg-red-100 text-red-800' };
        case 'rechazada':
            return { text: 'Rechazada', color: 'bg-red-100 text-red-800' };
        case 'pendiente':
            return { text: 'Pendiente', color: 'bg-yellow-100 text-yellow-800' };
        default:
            return { text: status, color: 'bg-gray-100 text-gray-800' };
    }
};

// Calcular días restantes desde la fecha de activación hasta expiración
const getRemainingDays = (activatedAt: string, expiresAt: string | null): number | null => {
    if (!expiresAt) return null;
    
    const now = new Date();
    const expiration = new Date(expiresAt);
    
    if (now > expiration) return 0;
    
    const diffTime = Math.abs(expiration.getTime() - now.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

// Mostrar el comprobante
const showReceipt = (membershipHistory: MembershipHistory) => {
    if (!membershipHistory.receipt_image) return;
    selectedMembershipId.value = membershipHistory.id;
    showReceiptDialog.value = true;
};

// Navegar a las páginas
const navigateTo = (path: string) => {
    router.visit(path);
};
</script>

<template>
    <Head title="Historial de Membresías" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-2">Historial de Membresías</h1>
                <p class="text-gray-600">
                    Revisa el historial completo de tus membresías y su estado.
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <Table>
                        <TableCaption>Historial de membresías adquiridas.</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Membresía</TableHead>
                                <TableHead>Precio pagado</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Método de pago</TableHead>
                                <TableHead>Fecha activación</TableHead>
                                <TableHead>Fecha expiración</TableHead>
                                <TableHead>Tiempo restante</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in membership_history" :key="item.id">
                                <TableCell class="font-medium">{{ item.membresia.nombre }}</TableCell>
                                <TableCell>{{ formatCurrency(item.precio_pagado) }}</TableCell>
                                <TableCell>
                                    <Badge :class="getStatusDetails(item.status).color">
                                        {{ getStatusDetails(item.status).text }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ item.payment_method }}</TableCell>
                                <TableCell>{{ formatDate(item.activated_at) }}</TableCell>
                                <TableCell>{{ formatDate(item.expires_at) }}</TableCell>
                                <TableCell>
                                    <span v-if="item.status === 'activa' && item.expires_at">
                                        {{ getRemainingDays(item.activated_at, item.expires_at) }} días
                                    </span>
                                    <span v-else-if="item.status === 'expirada'">
                                        Expirada
                                    </span>
                                    <span v-else-if="item.status === 'cancelada' || item.status === 'rechazada'">
                                        N/A
                                    </span>
                                    <span v-else>
                                        Sin expiración
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Button 
                                        v-if="item.receipt_image" 
                                        variant="outline"
                                        size="sm"
                                        class="flex items-center text-blue-600 hover:text-blue-800"
                                        @click="showReceipt(item)"
                                    >
                                        <Eye class="h-4 w-4 mr-1" />
                                        Ver comprobante
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="membership_history.length === 0">
                                <TableCell colspan="8" class="text-center py-4">
                                    No hay registros de membresías. 
                                    <Button variant="link" @click="navigateTo('/membership/select')">
                                        Adquirir una membresía
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <Button variant="outline" @click="navigateTo('/dashboard')">
                    Volver al dashboard
                </Button>
                <Button class="ml-3" @click="navigateTo('/membership/select')">
                    Adquirir nueva membresía
                </Button>
            </div>
        </div>

        <!-- Diálogo para mostrar comprobante -->
        <Dialog :open="showReceiptDialog" @update:open="showReceiptDialog = $event">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Comprobante de Pago</DialogTitle>
                    <DialogDescription>
                        Visualización del comprobante de pago subido
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4 flex justify-center">
                    <img v-if="selectedMembershipId" :src="route('membership.receipt', { membership: selectedMembershipId })" alt="Comprobante de pago" class="max-w-full max-h-[500px] object-contain shadow-md rounded" />
                </div>
                
                <DialogFooter>
                    <Button variant="outline" @click="showReceiptDialog = false">
                        Cerrar
                    </Button>
                    <a v-if="selectedMembershipId" :href="route('membership.receipt', { membership: selectedMembershipId })" target="_blank" rel="noopener noreferrer">
                        <Button variant="default">
                            Ver en nueva ventana
                        </Button>
                    </a>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template> 