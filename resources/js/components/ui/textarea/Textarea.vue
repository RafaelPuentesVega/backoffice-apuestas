<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  class: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue']);

const handleInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement;
  emit('update:modelValue', target.value);
};

const getClasses = () => {
  return `flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${props.class}`;
};
</script>

<template>
  <textarea
    :value="modelValue"
    @input="handleInput"
    :disabled="disabled"
    :placeholder="placeholder"
    :class="getClasses()"
  ></textarea>
</template> 