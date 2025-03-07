<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <div :class="[
    'text-sm [&_p]:leading-relaxed']">
    <slot />
  </div>
</template>