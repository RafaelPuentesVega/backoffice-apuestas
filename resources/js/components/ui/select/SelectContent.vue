<script setup>
import { inject, computed } from 'vue';

const select = inject('select');
const open = computed(() => select.open);
</script>

<template>
  <div v-if="open" class="relative z-50 w-full min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-80">
    <div class="max-h-[var(--radix-select-content-available-height)] overflow-auto">
      <slot />
    </div>
  </div>
</template> 