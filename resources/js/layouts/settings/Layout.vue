<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

const { filterNavItems } = usePermissions();

const sidebarNavItems: NavItem[] = [
    {
        title: 'Perfil',
        href: '/settings/profile',
    },
    {
        title: 'Contraseña',
        href: '/settings/password',
    },
    {
        title: 'Apariencia',
        href: '/settings/appearance',
        permission: 'ver-reportesss' 
    },
    {
        title: 'Wallet',
        href: '/settings/wallet',
    },
    {
        title: 'WhatsApp',
        href: '/settings/whatsapp',
    },
];

const filteredNavItems = filterNavItems(sidebarNavItems);


const currentPath = window.location.pathname;
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Configuración" description="Administra tu perfil y la configuración de tu cuenta" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-1/4">
                <nav class="flex flex-col space-x-0 space-y-1">
                    <Button
                        v-for="item in filteredNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-3xl">
                <section class="max-w-2xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
