<script setup lang="ts">
defineProps({
  class: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <td
    :class="[
      'p-4 align-middle [&:has([role=checkbox])]:pr-0'
    ]"
  >
    <slot />
  </td>
</template> 