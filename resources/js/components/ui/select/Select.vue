<script setup>
import { ref, provide, computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: undefined
  },
  defaultValue: {
    type: [String, Number],
    default: undefined
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

// Usar un enfoque más simple para el v-model
const selectedValue = ref(props.modelValue || props.defaultValue);

watch(() => props.modelValue, (newValue) => {
  selectedValue.value = newValue;
});

watch(selectedValue, (newValue) => {
  emit('update:modelValue', newValue);
});

const open = ref(false);

provide('select', {
  selectedValue,
  open,
  disabled: computed(() => props.disabled),
  required: computed(() => props.required),
  select: (value) => {
    selectedValue.value = value;
    open.value = false;
  }
});
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template> 