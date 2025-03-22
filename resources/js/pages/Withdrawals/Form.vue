<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

interface PaymentMethod {
  id: number;
  name: string;
}

interface Currency {
  code: string;
  name: string;
}

defineProps<{
  paymentMethods: PaymentMethod[];
  currencies: Currency[];
}>();

const form = useForm({
  payment_method_id: '',
  currency: '',
  amount: null as number | null,
  notes: ''
})

const submit = () => {
  form.post(route('withdrawals.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700">Payment Method</label>
      <select v-model="form.payment_method_id" class="mt-1 block w-full rounded-md border-gray-300">
        <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
          {{ method.name }}
        </option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Currency</label>
      <select v-model="form.currency" class="mt-1 block w-full rounded-md border-gray-300">
        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
          {{ currency.name }}
        </option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Amount</label>
      <input 
        type="number" 
        v-model="form.amount"
        step="0.01"
        class="mt-1 block w-full rounded-md border-gray-300"
      >
    </div>

    <button 
      type="submit"
      :disabled="form.processing"
      class="bg-blue-500 text-white px-4 py-2 rounded"
    >
      Submit Withdrawal
    </button>
  </form>
</template>
