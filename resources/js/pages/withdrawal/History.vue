<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { 
    Table, 
    TableBody, 
    TableCaption, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    withdrawals: Array
});

// Función para determinar el color del badge según el estado
const getStatusClass = (status) => {
    switch(status) {
        case 'completado':
            return 'bg-green-500 text-white';
        case 'pendiente':
            return 'bg-yellow-500 text-white';
        case 'rechazado':
            return 'bg-red-500 text-white';
        default:
            return 'bg-gray-500 text-white';
    }
};

// Formatear montos a formato de moneda
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(amount);
};
</script>

<template>
    <AppLayout>
        <Head title="Histórico de Retiros" />
        
        <div class="container mx-auto py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Histórico de Retiros</h1>
                <Link :href="route('withdrawal.create')">
                    <Button>Solicitar Retiro</Button>
                </Link>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <Table>
                    <TableCaption>Listado de retiros realizados</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">ID</TableHead>
                            <TableHead>Usuario</TableHead>
                            <TableHead>Cuenta</TableHead>
                            <TableHead>Monto</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Estado</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="withdrawal in withdrawals" :key="withdrawal.id">
                            <TableCell class="font-medium">{{ withdrawal.id }}</TableCell>
                            <TableCell>{{ withdrawal.user }}</TableCell>
                            <TableCell>{{ withdrawal.account }}</TableCell>
                            <TableCell>{{ formatCurrency(withdrawal.amount) }}</TableCell>
                            <TableCell>{{ withdrawal.date }}</TableCell>
                            <TableCell>
                                <Badge :class="getStatusClass(withdrawal.status)">
                                    {{ withdrawal.status }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template> 