<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsList as BaseTabsList } from 'radix-vue';

interface Props {
  className?: string;
}

withDefaults(defineProps<Props>(), {
  className: undefined,
});
</script>

<template>
  <BaseTabsList
    :class="
      cn(
        'inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground',
        className
      )
    "
  >
    <slot />
  </BaseTabsList>
</template> 