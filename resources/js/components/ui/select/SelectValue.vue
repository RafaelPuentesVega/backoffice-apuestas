<script setup>
import { inject, computed } from 'vue';

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Seleccionar opción'
  }
});

const select = inject('select');
const selectedValue = computed(() => select.selectedValue);
</script>

<template>
  <span class="block truncate">
    <slot v-if="selectedValue" />
    <span v-else class="text-muted-foreground">{{ placeholder }}</span>
  </span>
</template> 