<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { TabsList, TabsContent, TabsTrigger, Tabs } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Eye, Check, X, Search } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import debounce from 'lodash.debounce';

interface Withdrawal {
  id: number;
  user: {
    id: number;
    name: string;
    email: string;
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

interface Stats {
  pendientes: number;
  completados: number;
  rechazados: number;
  total: number;
}

interface PageProps {
  withdrawals: {
    data: Withdrawal[];
    meta: {
      current_page: number;
      from: number;
      last_page: number;
      links: any[];
      path: string;
      per_page: number;
      to: number;
      total: number;
    }
  };
  stats: Stats;
  filters: {
    status: string;
    search: string;
  };
}

defineProps<PageProps>();

const search = ref('');
const activeTab = ref('all');

// Inicializar valores con los filtros actuales
watch(() => activeTab.value, (newValue) => {
  router.get(route('admin.withdrawals.index'), { 
    status: newValue,
    search: search.value 
  }, {
    preserveState: true,
    replace: true
  });
});

const updateSearch = debounce((value: string) => {
  router.get(route('admin.withdrawals.index'), { 
    status: activeTab.value,
    search: value 
  }, {
    preserveState: true,
    replace: true
  });
}, 500);

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
  }
];
</script>

<template>
  <Head title="Gestión de Retiros" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold mb-6">Gestión de Retiros</h1>

      <!-- Tarjetas de estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Total de Retiros</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Pendientes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-amber-600">{{ stats.pendientes }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Completados</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.completados }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Rechazados</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.rechazados }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filtros y búsqueda -->
      <div class="mb-6 flex flex-col md:flex-row gap-4">
        <Tabs v-model:modelValue="activeTab" class="w-full md:w-2/3" default-value="all">
          <TabsList class="grid w-full grid-cols-4">
            <TabsTrigger value="all">Todos</TabsTrigger>
            <TabsTrigger value="pendiente">Pendientes</TabsTrigger>
            <TabsTrigger value="completado">Completados</TabsTrigger>
            <TabsTrigger value="rechazado">Rechazados</TabsTrigger>
          </TabsList>
        </Tabs>

        <div class="w-full md:w-1/3 relative">
          <Input 
            v-model="search" 
            placeholder="Buscar por nombre o email..."
            class="pr-10"
            @input="updateSearch(search)"
          />
          <Search class="absolute right-3 top-2.5 h-4 w-4 text-gray-500" />
        </div>
      </div>

      <!-- Tabla de retiros -->
      <Card>
        <CardHeader>
          <CardTitle>Lista de Retiros</CardTitle>
          <CardDescription>
            Gestiona los retiros solicitados por los usuarios
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-3 px-4">Usuario</th>
                  <th class="text-left py-3 px-4">Monto</th>
                  <th class="text-left py-3 px-4">Tipo</th>
                  <th class="text-left py-3 px-4">Billetera</th>
                  <th class="text-left py-3 px-4">Estado</th>
                  <th class="text-left py-3 px-4">Fecha</th>
                  <th class="text-right py-3 px-4">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="py-3 px-4">
                    <div class="font-medium">{{ withdrawal.user.name }}</div>
                    <div class="text-sm text-gray-500">{{ withdrawal.user.email }}</div>
                  </td>
                  <td class="py-3 px-4 font-medium">
                    {{ formatCurrency(withdrawal.amount) }}
                  </td>
                  <td class="py-3 px-4">
                    {{ getBalanceTypeLabel(withdrawal.balance_type) }}
                  </td>
                  <td class="py-3 px-4">
                    <div class="max-w-[150px] truncate" :title="withdrawal.wallet_address">
                      {{ withdrawal.wallet_address }}
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <Badge :class="getStatusColor(withdrawal.status)">
                      {{ getStatusLabel(withdrawal.status) }}
                    </Badge>
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-500">
                    <div>Creado: {{ formatDate(withdrawal.created_at) }}</div>
                    <div v-if="withdrawal.processed_at">
                      Procesado: {{ formatDate(withdrawal.processed_at) }}
                    </div>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <div class="flex justify-end gap-2">
                      <Link :href="route('admin.withdrawals.show', withdrawal.id)">
                        <Button variant="outline" size="sm" class="h-8 w-8 p-0">
                          <Eye class="h-4 w-4" />
                        </Button>
                      </Link>

                      <Link 
                        v-if="withdrawal.status === 'pendiente'"
                        :href="route('admin.withdrawals.show', withdrawal.id) + '?action=approve'"
                        class="text-green-600 hover:text-green-800"
                      >
                        <Button variant="outline" size="sm" class="h-8 w-8 p-0 border-green-200 text-green-600 hover:text-green-700 hover:border-green-300">
                          <Check class="h-4 w-4" />
                        </Button>
                      </Link>

                      <Link 
                        v-if="withdrawal.status === 'pendiente'"
                        :href="route('admin.withdrawals.show', withdrawal.id) + '?action=reject'"
                        class="text-red-600 hover:text-red-800"
                      >
                        <Button variant="outline" size="sm" class="h-8 w-8 p-0 border-red-200 text-red-600 hover:text-red-700 hover:border-red-300">
                          <X class="h-4 w-4" />
                        </Button>
                      </Link>
                    </div>
                  </td>
                </tr>
                <tr v-if="withdrawals.data.length === 0">
                  <td colspan="7" class="py-6 text-center text-gray-500">
                    No se encontraron retiros que coincidan con los criterios de búsqueda
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div v-if="withdrawals.meta && withdrawals.meta.last_page > 1" class="flex justify-between items-center mt-6">
            <div class="text-sm text-gray-600">
              Mostrando {{ withdrawals.meta.from }} a {{ withdrawals.meta.to }} de {{ withdrawals.meta.total }} resultados
            </div>
            <div class="flex gap-1">
              <Link 
                v-for="link in withdrawals.meta.links" 
                :key="link.url" 
                :href="link.url" 
                class="px-3 py-1 text-sm border rounded"
                :class="[
                  link.active ? 'bg-blue-50 border-blue-200 text-blue-600' : 'border-gray-200 hover:bg-gray-50',
                  !link.url ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                :disabled="!link.url"
                preserve-scroll
              >
                <span v-html="link.label"></span>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template> 