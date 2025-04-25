<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LogOut, Settings, Wallet, Users, DollarSign, CreditCard, Check, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();

const hasMembership = computed(() => props.user?.membership_id && props.user.membership_id > 0);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    
    <!-- Saldos del Usuario -->
    <DropdownMenuLabel class="p-2 font-medium text-sm">Mis Saldos</DropdownMenuLabel>
    <div class="px-3 py-2 space-y-2 text-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <DollarSign class="h-4 w-4 mr-2 text-green-600" />
                <span>Capital:</span>
            </div>
            <span class="font-semibold">{{ user?.capital_balance ?? '0.00' }} USD</span>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <Wallet class="h-4 w-4 mr-2 text-blue-600" />
                <span>Ganancias:</span>
            </div>
            <span class="font-semibold">{{ user?.earnings_balance ?? '0.00' }} USD</span>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <Users class="h-4 w-4 mr-2 text-purple-600" />
                <span>Ganancias Red:</span>
            </div>
            <span class="font-semibold">{{ user?.network_balance ?? '0.00' }} USD</span>
        </div>
    </div>
    
    <!-- Información de Membresía -->
    <DropdownMenuSeparator />
    <DropdownMenuLabel class="p-2 font-medium text-sm">Mi Membresía</DropdownMenuLabel>
    <div class="px-3 py-2 space-y-2 text-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <CreditCard class="h-4 w-4 mr-2 text-indigo-600" />
                <span>Plan:</span>
            </div>
            <span v-if="user?.membership?.nombre" class="font-semibold">{{ user.membership.nombre }}</span>
            <span v-else class="text-gray-500">Sin membresía</span>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="h-4 w-4 mr-2"></span>
                <span>Estado:</span>
            </div>
            <div class="flex items-center">
                <span v-if="hasMembership" class="text-green-600 font-semibold flex items-center">
                    <Check class="h-4 w-4 mr-1" />
                    Activa
                </span>
                <span v-else class="text-red-600 font-semibold flex items-center">
                    <X class="h-4 w-4 mr-1" />
                    Inactiva
                </span>
            </div>
        </div>
        <div class="flex items-center justify-between" v-if="user?.membership?.porcentaje_rendimiento !== undefined">
            <div class="flex items-center">
                <span class="h-4 w-4 mr-2"></span>
                <span>Rendimiento:</span>
            </div>
            <span class="font-semibold">{{ user.membership.porcentaje_rendimiento }}%</span>
        </div>
    </div>
    
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="route('profile.edit')" as="button">
                <Settings class="mr-2 h-4 w-4" />
                Ajustes
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full cursor-pointer" method="post" :href="route('logout')" as="button">
            <LogOut class="mr-2 h-4 w-4" />
            Cerrar sesión
        </Link>
    </DropdownMenuItem>
</template>
