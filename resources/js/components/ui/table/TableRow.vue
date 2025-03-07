<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <tr
    :class="[
      'border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted'
    ]"
  >
    <slot />
  </tr>
</template> 