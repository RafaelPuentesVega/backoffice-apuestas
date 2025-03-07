<script setup lang="ts">
import { SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { onClickOutside } from '@vueuse/core';

defineProps<{
    item: NavItem;
}>();

const page = usePage<SharedData>();
const isSubmenuOpen = ref(false);
const { state } = useSidebar();
const submenuRef = ref(null);

const isCollapsed = computed(() => state.value === 'collapsed');

const toggleSubmenu = () => {
    isSubmenuOpen.value = !isSubmenuOpen.value;
};

const isActive = (href: string) => {
    return href === page.url || page.url.startsWith(href + '/');
};

onClickOutside(submenuRef, () => {
    if (isSubmenuOpen.value) {
        isSubmenuOpen.value = false;
    }
});

onMounted(() => {
    const handleRouteChange = () => {
        isSubmenuOpen.value = false;
    };
    
    window.addEventListener('popstate', handleRouteChange);
    
    onUnmounted(() => {
        window.removeEventListener('popstate', handleRouteChange);
    });
});
</script>

<template>
    <SidebarMenuItem>
        <template v-if="item.children && item.children.length > 0">
            <div ref="submenuRef">
                <SidebarMenuButton 
                    @click="toggleSubmenu" 
                    :is-active="isActive(item.href)"
                    class="w-full flex justify-between"
                    :tooltip="isCollapsed ? item.title : undefined"
                >
                    <div class="flex items-center">
                        <component :is="item.icon" v-if="item.icon" class="mr-2 h-4 w-4" />
                        <span>{{ item.title }}</span>
                    </div>
                    <component :is="isSubmenuOpen ? ChevronDown : ChevronRight" class="h-4 w-4 transition-transform duration-200" />
                </SidebarMenuButton>
                
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <div v-if="isSubmenuOpen && !isCollapsed" class="pl-6 mt-1 space-y-1 origin-top">
                        <SidebarMenuItem v-for="child in item.children" :key="child.title">
                            <SidebarMenuButton as-child :is-active="isActive(child.href)">
                                <Link :href="child.href" class="flex items-center">
                                    <component :is="child.icon" v-if="child.icon" class="mr-2 h-4 w-4" />
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </div>
                </transition>
                
                <transition
                    enter-active-class="transition durati
                <!-- Submenú flotante para sidebar colapsado -->on-200 ease-out"
                    enter-from-class="transform -translate-x-2 opacity-0"
                    enter-to-class="transform translate-x-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform translate-x-0 opacity-100"
                    leave-to-class="transform -translate-x-2 opacity-0"
                >
                    <div v-if="isSubmenuOpen && isCollapsed" 
                        class="absolute left-full top-0 z-50 ml-2 w-48 rounded-md border border-border bg-popover p-2 shadow-md"
                        style="margin-top: -8px;">
                        <SidebarMenuItem v-for="child in item.children" :key="child.title">
                            <SidebarMenuButton as-child :is-active="isActive(child.href)">
                                <Link :href="child.href" class="flex items-center">
                                    <component :is="child.icon" v-if="child.icon" class="mr-2 h-4 w-4" />
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </div>
                </transition>
            </div>
        </template>
        
        <template v-else>
            <SidebarMenuButton as-child :is-active="isActive(item.href)" :tooltip="isCollapsed ? item.title : undefined">
                <Link :href="item.href" class="flex items-center">
                    <component :is="item.icon" v-if="item.icon" class="mr-2 h-4 w-4" />
                    <span>{{ item.title }}</span>
                </Link>
            </SidebarMenuButton>
        </template>
    </SidebarMenuItem>
</template> 