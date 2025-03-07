<script setup>
import { inject, computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps({
  class: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Seleccionar opción'
  }
});

const select = inject('select');
const open = computed(() => select.open);
const disabled = computed(() => select.disabled);

const toggleOpen = () => {
  if (disabled.value) return;
  select.open = !select.open;
};

const getClasses = () => {
  return `flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${props.class}`;
};
</script>

<template>
  <button
    type="button"
    :class="getClasses()"
    :aria-expanded="open"
    :disabled="disabled"
    @click="toggleOpen"
  >
    <slot />
    <ChevronDown class="h-4 w-4 opacity-50" />
  </button>
</template> 