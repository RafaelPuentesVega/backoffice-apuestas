<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <caption
    :class="[
      'mt-4 text-sm text-muted-foreground'
    ]"
  >
    <slot />
  </caption>
</template> 