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
  const baseClasses = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
  
  let variantClasses = '';
  if (props.variant === 'default') {
    variantClasses = 'border-transparent bg-primary text-primary-foreground hover:bg-primary/80';
  } else if (props.variant === 'secondary') {
    variantClasses = 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80';
  } else if (props.variant === 'destructive') {
    variantClasses = 'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80';
  } else if (props.variant === 'outline') {
    variantClasses = 'text-foreground';
  }
  
  return `${baseClasses} ${variantClasses} ${props.class}`;
};
</script>

<template>
  <div :class="getClasses()">
    <slot />
  </div>
</template> 