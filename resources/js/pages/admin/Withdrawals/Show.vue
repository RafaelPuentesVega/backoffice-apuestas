<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { 
  Dialog, 
  DialogTrigger, 
  DialogContent, 
  DialogHeader, 
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog';
import { 
  ArrowLeft, 
  Check, 
  X, 
  Calendar, 
  Wallet, 
  User,
  DollarSign,
  Tag
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Withdrawal {
  id: number;
  user: {
    id: number;
    name: string;
    email: string;
    wallet?: {
      address: string;
      type: string;
    };
  };
  amount: number;
  withdrawal_type: string;
  balance_type: string;
  wallet_address: string;
  status: string;
  description: string;
  created_at: string;
  processed_at: string | null;
}

interface PageProps {
  withdrawal: Withdrawal;
}

const props = defineProps<PageProps>();

// Modales
const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);

// Verificar si hay parámetros en la URL para abrir diálogos automáticamente
onMounted(() => {
  // Obtener los parámetros de la URL
  const urlParams = new URLSearchParams(window.location.search);
  const action = urlParams.get('action');
  
  // Abrir el diálogo correspondiente según el parámetro
  if (action === 'approve' && props.withdrawal.status === 'pendiente') {
    approveDialogOpen.value = true;
  } else if (action === 'reject' && props.withdrawal.status === 'pendiente') {
    rejectDialogOpen.value = true;
  }
  
  // Limpiar el parámetro de la URL después de procesar
  if (action) {
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
  }
});

// Formularios para las acciones
const approveForm = useForm({});
const rejectForm = useForm({
  reason: '',
});

const submitApprove = () => {
  // Usar URL directa para evitar problemas de routing
  approveForm.post(`/admin/withdrawals/${props.withdrawal.id}/approve`, {
    onSuccess: () => {
      approveDialogOpen.value = false;
      window.location.reload(); // Recargar para ver cambios
    },
    preserveScroll: true,
    onError: (errors) => {
      console.error('Error al aprobar el retiro:', errors);
    }
  });
};

const submitReject = () => {
  // Validar que hay una razón
  if (!rejectForm.reason) {
    alert('Debe especificar una razón para rechazar el retiro');
    return;
  }
  
  // Usar URL directa para evitar problemas de routing
  rejectForm.post(`/admin/withdrawals/${props.withdrawal.id}/reject`, {
    onSuccess: () => {
      rejectDialogOpen.value = false;
      rejectForm.reset();
      window.location.reload(); // Recargar para ver cambios
    },
    preserveScroll: true,
    onError: (errors) => {
      console.error('Error al rechazar el retiro:', errors);
    }
  });
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2
  }).format(value);
};

const formatDate = (dateString) => {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('es-AR', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'pendiente': return 'Pendiente';
    case 'completado': return 'Completado';
    case 'rechazado': return 'Rechazado';
    default: return status;
  }
};

const getStatusColor = (status) => {
  switch (status) {
    case 'pendiente': return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'completado': return 'bg-green-100 text-green-800 border-green-200';
    case 'rechazado': return 'bg-red-100 text-red-800 border-red-200';
    default: return 'bg-gray-100 text-gray-800 border-gray-200';
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
    href: '/admin/dashboard',
  },
  {
    title: 'Gestión de Retiros',
    href: '/admin/withdrawals',
  },
  {
    title: `Retiro #${props.withdrawal.id}`,
    href: `/admin/withdrawals/${props.withdrawal.id}`,
  }
];
</script>

<template>
  <Head :title="`Retiro #${withdrawal.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-4">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold">Detalles del Retiro #{{ withdrawal.id }}</h1>
          <p class="text-gray-500">
            Solicitud del {{ formatDate(withdrawal.created_at) }}
          </p>
        </div>
        <Link :href="route('admin.withdrawals.index')" class="flex items-center text-gray-600 hover:text-black">
          <ArrowLeft class="w-4 h-4 mr-1" />
          Volver a la lista
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información del retiro -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center">
                Detalles de la Solicitud
                <Badge :class="getStatusColor(withdrawal.status)" class="ml-3">
                  {{ getStatusLabel(withdrawal.status) }}
                </Badge>
              </CardTitle>
              <CardDescription>
                Información completa sobre la solicitud de retiro
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex items-start">
                    <DollarSign class="w-5 h-5 mr-3 text-gray-400 mt-0.5" />
                    <div>
                      <div class="text-sm text-gray-500">Monto solicitado</div>
                      <div class="font-medium text-lg">{{ formatCurrency(withdrawal.amount) }}</div>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <Tag class="w-5 h-5 mr-3 text-gray-400 mt-0.5" />
                    <div>
                      <div class="text-sm text-gray-500">Tipo de balance</div>
                      <div class="font-medium">{{ getBalanceTypeLabel(withdrawal.balance_type) }}</div>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <Wallet class="w-5 h-5 mr-3 text-gray-400 mt-0.5" />
                    <div>
                      <div class="text-sm text-gray-500">Dirección de billetera</div>
                      <div class="font-medium break-all">{{ withdrawal.wallet_address }}</div>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <Calendar class="w-5 h-5 mr-3 text-gray-400 mt-0.5" />
                    <div>
                      <div class="text-sm text-gray-500">Fechas</div>
                      <div class="font-medium">Solicitado: {{ formatDate(withdrawal.created_at) }}</div>
                      <div v-if="withdrawal.processed_at" class="font-medium">
                        Procesado: {{ formatDate(withdrawal.processed_at) }}
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="withdrawal.description" class="mt-6 pt-6 border-t border-gray-100">
                  <div class="text-sm text-gray-500 mb-2">Detalles / Razón:</div>
                  <div class="bg-gray-50 p-3 rounded">{{ withdrawal.description }}</div>
                </div>
              </div>
            </CardContent>
            <CardFooter v-if="withdrawal.status === 'pendiente'" class="flex justify-end space-x-3">
              <Dialog v-model:open="rejectDialogOpen">
                <DialogTrigger asChild>
                  <Button variant="outline" class="border-red-200 text-red-600 hover:text-red-700 hover:border-red-300">
                    <X class="h-4 w-4 mr-2" />
                    Rechazar
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Rechazar Retiro</DialogTitle>
                    <DialogDescription>
                      Al rechazar este retiro, el monto será devuelto al balance del usuario. Por favor indique la razón.
                    </DialogDescription>
                  </DialogHeader>
                  <div class="py-4">
                    <div class="mb-4">
                      <div class="font-medium mb-2">Razón del rechazo:</div>
                      <Textarea
                        v-model="rejectForm.reason"
                        placeholder="Ingrese la razón por la que rechaza esta solicitud..."
                        :class="{ 'border-red-500': rejectForm.errors.reason }"
                      ></Textarea>
                      <div v-if="rejectForm.errors.reason" class="text-red-500 text-sm mt-1">
                        {{ rejectForm.errors.reason }}
                      </div>
                    </div>
                  </div>
                  <DialogFooter>
                    <Button variant="outline" @click="rejectDialogOpen = false">Cancelar</Button>
                    <Button 
                      variant="destructive" 
                      @click="submitReject"
                      :disabled="rejectForm.processing"
                    >
                      {{ rejectForm.processing ? 'Procesando...' : 'Confirmar Rechazo' }}
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>

              <Dialog v-model:open="approveDialogOpen">
                <DialogTrigger asChild>
                  <Button variant="default" class="bg-green-600 hover:bg-green-700">
                    <Check class="h-4 w-4 mr-2" />
                    Aprobar
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Aprobar Retiro</DialogTitle>
                    <DialogDescription>
                      ¿Está seguro que desea aprobar este retiro? Esta acción no se puede deshacer.
                    </DialogDescription>
                  </DialogHeader>
                  <div class="py-4">
                    <div class="bg-gray-50 p-4 rounded mb-4">
                      <div class="flex justify-between mb-2">
                        <span class="font-medium">Usuario:</span>
                        <span>{{ withdrawal.user.name }}</span>
                      </div>
                      <div class="flex justify-between mb-2">
                        <span class="font-medium">Monto:</span>
                        <span>{{ formatCurrency(withdrawal.amount) }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="font-medium">Billetera:</span>
                        <span class="text-right">{{ withdrawal.wallet_address }}</span>
                      </div>
                    </div>
                  </div>
                  <DialogFooter>
                    <Button variant="outline" @click="approveDialogOpen = false">Cancelar</Button>
                    <Button 
                      variant="default" 
                      @click="submitApprove"
                      :disabled="approveForm.processing"
                    >
                      {{ approveForm.processing ? 'Procesando...' : 'Confirmar Aprobación' }}
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            </CardFooter>
          </Card>
        </div>

        <!-- Información del usuario -->
        <div>
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center">
                <User class="w-5 h-5 mr-2" />
                Información del Usuario
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div>
                  <div class="text-sm text-gray-500">Nombre completo</div>
                  <div class="font-medium">{{ withdrawal.user.name }}</div>
                </div>
                <div>
                  <div class="text-sm text-gray-500">Email</div>
                  <div class="font-medium">{{ withdrawal.user.email }}</div>
                </div>
                <div v-if="withdrawal.user.wallet">
                  <div class="text-sm text-gray-500">Billetera registrada</div>
                  <div class="font-medium break-all">{{ withdrawal.user.wallet.address }}</div>
                  <div class="text-sm text-gray-500 mt-1">Tipo: {{ withdrawal.user.wallet.type }}</div>
                </div>
              </div>
            </CardContent>
            <CardFooter>
              <a :href="`/admin/users/${withdrawal.user.id}`" class="w-full">
                <Button variant="outline" class="w-full">
                  Ver perfil completo
                </Button>
              </a>
            </CardFooter>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template> 