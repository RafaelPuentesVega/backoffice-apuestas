<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <h5
    :class="[
      'mb-1 font-medium leading-none tracking-tight',
      
    ]"
  >
    <slot />
  </h5>
</template> 