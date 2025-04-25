<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

// Definir el tipo para los retiros
interface Withdrawal {
    id: number;
    amount: number;
    status: string;
    date: string;
    user: string;
    account: string;
}

defineProps<{
    withdrawals: Withdrawal[];
}>();

// Función para determinar el color del badge según el estado
const getStatusClass = (status: string): string => {
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
const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

// Método para formatear fecha y hora para Bogotá, Colombia
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleString('es-CO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
    timeZone: 'America/Bogota'
  });
};

// Función para formatear la dirección de wallet
const formatWalletAddress = (address: string): string => {
  if (!address) return '';
  
  if (address.length <= 8) return address;
  
  const start = address.slice(0, 4);
  const end = address.slice(-4);
  
  return `${start}********${end}`;
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
            
            <div class="rounded-lg shadow-md overflow-hidden">
                <Table>
                    <TableCaption>Listado de retiros realizados</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">ID</TableHead>
                            <TableHead>Wallet</TableHead>
                            <TableHead>Monto</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Estado</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="withdrawal in withdrawals" :key="withdrawal.id">
                            <TableCell class="font-medium">{{ withdrawal.id }}</TableCell>
                            <TableCell>{{ formatWalletAddress(withdrawal.wallet_address) }}</TableCell>
                            <TableCell>{{ formatCurrency(withdrawal.amount) }}</TableCell>
                            <TableCell>{{ formatDate(withdrawal.created_at)}}</TableCell>
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