<script setup lang="ts">
import { onMounted, onUnmounted, inject } from 'vue'
import { TransitionRoot } from '@headlessui/vue'

const select = inject('select') as any

const handleClickOutside = (event: MouseEvent) => {
  if (
    !(event.target as Element).closest('.select-content') &&
    !(event.target as Element).closest('button')
  ) {
    select.closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <TransitionRoot
    :show="select.isOpen.value"
    enter="transition ease-out duration-200"
    enter-from="opacity-0 translate-y-1"
    enter-to="opacity-100 translate-y-0"
    leave="transition ease-in duration-150"
    leave-from="opacity-100 translate-y-0"
    leave-to="opacity-0 translate-y-1"
    as="template"
  >
    <div
      class="select-content absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover text-popover-foreground shadow-md outline-none"
    >
      <div class="p-1">
        <slot />
      </div>
    </div>
  </TransitionRoot>
</template>
