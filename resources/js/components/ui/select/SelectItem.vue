<script setup>
import { inject, computed } from 'vue';
import { Check } from 'lucide-vue-next';

const props = defineProps({
  value: {
    type: [String, Number],
    required: true
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const select = inject('select');
const selectedValue = computed(() => select.selectedValue);
const isSelected = computed(() => selectedValue.value === props.value);

const handleSelect = () => {
  if (props.disabled) return;
  select.select(props.value);
};

const getClasses = () => {
  let classes = 'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground';
  
  if (props.disabled) {
    classes += ' pointer-events-none opacity-50';
  }
  
  if (isSelected.value) {
    classes += ' bg-accent text-accent-foreground';
  }
  
  return classes;
};
</script>

<template>
  <div
    :class="getClasses()"
    @click="handleSelect"
  >
    <span v-if="isSelected" class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <Check class="h-4 w-4" />
    </span>
    <slot />
  </div>
</template> 