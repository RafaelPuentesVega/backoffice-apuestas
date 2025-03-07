<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <tfoot
    :class="[
      'border-t bg-muted/50 font-medium [&>tr]:last:border-b-0'
    ]"
  >
    <slot />
  </tfoot>
</template> 