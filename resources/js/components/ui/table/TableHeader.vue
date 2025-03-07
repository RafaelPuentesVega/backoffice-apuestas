<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <thead :class="['[&_tr]:border-b']">
    <slot />
  </thead>
</template> 