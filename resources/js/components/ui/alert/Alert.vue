<script setup>
const props = defineProps({
  variant: {
    type: String,
    default: 'default'
  },
  class: {
    type: String,
    default: ''
  }
});

const getClasses = () => {
  const baseClasses = 'relative w-full rounded-lg border p-4';
  const variantClasses = props.variant === 'default' 
    ? 'bg-background text-foreground' 
    : 'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive';
  
  return `${baseClasses} ${variantClasses} ${props.class}`;
};
</script>

<template>
  <div :class="getClasses()" role="alert">
    <slot />
  </div>
</template> 