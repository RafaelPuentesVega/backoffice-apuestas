<script setup lang="ts">
import { Check } from 'lucide-vue-next'
import { computed, inject } from 'vue'

const props = defineProps<{
  value: string
}>()

const select = inject('select') as any

// Verificar si este item está seleccionado comparando con el valor actual
const isSelected = computed(() => {
  return select.selectedValue.value === props.value
})

// Manejar la selección de este item
const handleSelect = () => {
  select.updateValue(props.value)
}
</script>

<template>
  <div
    class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
    :class="{ 'bg-accent text-accent-foreground': isSelected }"
    @click="handleSelect"
    role="option"
    :aria-selected="isSelected"
  >
    <span v-if="isSelected" class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <Check class="h-4 w-4" />
    </span>
    <slot />
  </div>
</template>