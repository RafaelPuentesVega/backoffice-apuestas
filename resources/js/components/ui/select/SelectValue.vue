<script setup lang="ts">
import { inject, computed } from 'vue'

const props = defineProps<{
  placeholder?: string
}>()

const select = inject('select') as any

// Computar si hay un valor seleccionado
const hasValue = computed(() => {
  return select.selectedValue.value !== undefined && 
         select.selectedValue.value !== null && 
         select.selectedValue.value !== ''
})

// Obtener el placeholder
const placeholderText = computed(() => props.placeholder || 'Seleccione una opción')
</script>

<template>
  <span class="block truncate">
    <span v-if="hasValue">
      <slot />
    </span>
    <span v-else class="text-muted-foreground">
      {{ placeholderText }}
    </span>
  </span>
</template>