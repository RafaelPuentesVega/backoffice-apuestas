<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Calendar, Mail, User } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';

interface Props {
    directReferrals: {
        id: number;
        name: string;
        email: string;
        created_at: string;
    }[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Red',
        href: '/network',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user;

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Mi Red - Nivel 1" />

        <div class="container mx-auto py-6">
            <HeadingSmall 
                title="Mi Red - Nivel 1" 
                description="Usuarios registrados directamente bajo tu patrocinio" 
            />

            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div v-if="directReferrals.length === 0" class="p-6 text-center text-gray-500">
                    <p>No tienes usuarios registrados en tu red todavía.</p>
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <User class="h-4 w-4 mr-2" />
                                        Nombre
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <Mail class="h-4 w-4 mr-2" />
                                        Correo Electrónico
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <Calendar class="h-4 w-4 mr-2" />
                                        Fecha de Registro
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="referral in directReferrals" :key="referral.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <span class="text-gray-600 dark:text-gray-300 font-medium">{{ referral.name.charAt(0) }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ referral.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ referral.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(referral.created_at) }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
