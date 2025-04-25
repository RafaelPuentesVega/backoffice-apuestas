<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsContent as BaseTabsContent } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

interface Props {
  value: string;
  class?: HTMLAttributes['class'];
  forceMount?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  forceMount: false,
  class: '',
});
</script>

<template>
  <BaseTabsContent
    :value="value"
    :force-mount="forceMount"
    :class="
      cn(
        'mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        props.class,
      )
    "
  >
    <slot />
  </BaseTabsContent>
</template> 