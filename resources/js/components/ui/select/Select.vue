<script setup lang="ts">
import { computed, provide, ref, toRef, watch } from 'vue'

const props = defineProps<{
  modelValue?: string
  defaultValue?: string
  disabled?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

// Inicializar con el valor del modelo o el valor por defecto
const selectedValue = ref(props.modelValue || props.defaultValue || '')
const isOpen = ref(false)

// Observar cambios en el modelValue para mantener sincronizado
watch(
  () => props.modelValue,
  (value) => {
    if (value !== undefined) {
      selectedValue.value = value
    }
  },
  { immediate: true } // Ejecutar inmediatamente para sincronizar al inicio
)

// Actualizar el valor cuando se selecciona una opción
const updateValue = (value: string) => {
  selectedValue.value = value
  emit('update:modelValue', value)
  isOpen.value = false
}

// Alternar el estado del dropdown
const toggleOpen = () => {
  // Siempre permitir abrir el dropdown a menos que explícitamente se pase disabled=true
  if (props.disabled !== true) {
    isOpen.value = !isOpen.value
  }
}

// Cerrar el dropdown
const closeDropdown = () => {
  isOpen.value = false
}

// Proporcionar el contexto a los componentes hijos
provide('select', {
  selectedValue: toRef(selectedValue),
  isOpen: toRef(isOpen),
  toggleOpen,
  closeDropdown,
  updateValue,
  disabled: computed(() => props.disabled === true)
})
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template>
