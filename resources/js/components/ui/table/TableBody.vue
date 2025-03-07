<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <tbody
    :class="[
      '[&_tr:last-child]:border-0'
    ]"
  >
    <slot />
  </tbody>
</template> 