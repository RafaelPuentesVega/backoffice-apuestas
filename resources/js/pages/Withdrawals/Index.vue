<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

interface Withdrawal {
  id: number;
  created_at: string;
  amount: number;
  payment_method: string;
  status: string;
}

interface WithdrawalsPagination {
  data: Withdrawal[];
  // Otros campos de paginación si los hay
}

defineProps<{
  withdrawals: WithdrawalsPagination;
}>();

const columns = [
  { key: 'date', label: 'Date' },
  { key: 'amount', label: 'Amount' },
  { key: 'payment_method', label: 'Payment Method' },
  { key: 'status', label: 'Status' }
]
</script>

<template>
  <Head title="Withdrawals" />

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column.key" class="px-6 py-3 text-left">
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="withdrawal in withdrawals.data" :key="withdrawal.id">
            <td class="px-6 py-4">{{ withdrawal.created_at }}</td>
            <td class="px-6 py-4">{{ withdrawal.amount }}</td>
            <td class="px-6 py-4">{{ withdrawal.payment_method }}</td>
            <td class="px-6 py-4">
              <span :class="[
                'px-2 py-1 rounded-full text-xs',
                withdrawal.status === 'approved' ? 'bg-green-100 text-green-800' :
                withdrawal.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                'bg-red-100 text-red-800'
              ]">
                {{ withdrawal.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>