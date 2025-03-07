<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <div class="w-full overflow-auto">
    <table
      :class="[
        'w-full caption-bottom text-sm'
      ]"
    >
      <slot />
    </table>
  </div>
</template> 