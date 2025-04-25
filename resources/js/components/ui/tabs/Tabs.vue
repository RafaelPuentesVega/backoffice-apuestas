<script setup lang="ts">
import { TabsRoot } from 'radix-vue';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Props {
  defaultValue?: string;
  modelValue?: string;
  className?: string;
}

const props = withDefaults(defineProps<Props>(), {
  defaultValue: undefined,
  modelValue: undefined,
  className: '',
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const onValueChange = (val: string) => {
  emit('update:modelValue', val);
};
</script>

<template>
  <TabsRoot
    :model-value="modelValue"
    :default-value="defaultValue"
    :class="cn('w-full', className)"
    @update:model-value="onValueChange"
  >
    <slot />
  </TabsRoot>
</template> 