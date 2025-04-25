<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Pencil, Trash2, Plus } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';

interface Membresia {
    id: number;
    nombre: string;
    precio: number;
    comision_directa: number;
    porcentaje_rendimiento: number;
    porcentaje_comision_sponsor: number;
    created_at?: string;
    updated_at?: string;
}

defineProps<{
    membresias: Membresia[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administración',
        href: '/admin',
    },
    {
        title: 'Membresías',
        href: route('admin.memberships.index'),
    },
];

const showDeleteDialog = ref(false);
const selectedMembership = ref<Membresia | null>(null);

const confirmDelete = (membresia: Membresia) => {
    selectedMembership.value = membresia;
    showDeleteDialog.value = true;
};

const deleteMembresia = () => {
    if (selectedMembership.value) {
        window.location.href = route('admin.memberships.destroy', { membership: selectedMembership.value.id }) + '?_method=DELETE';
    }
};
</script>

<template>
    <Head title="Gestión de Membresías" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Gestión de Membresías</CardTitle>
                        <CardDescription>
                            Administre las membresías disponibles en el sistema
                        </CardDescription>
                    </div>
                    <Button as="a" :href="route('admin.memberships.create')">
                        <Plus class="h-4 w-4 mr-2" />
                        Nueva Membresía
                    </Button>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-3 px-4 text-left">Nombre</th>
                                    <th class="py-3 px-4 text-left">Precio</th>
                                    <th class="py-3 px-4 text-left">Comisión Directa</th>
                                    <th class="py-3 px-4 text-left">% Rendimiento</th>
                                    <th class="py-3 px-4 text-left">% Comisión Sponsor</th>
                                    <th class="py-3 px-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="membresias.length === 0">
                                    <td colspan="6" class="py-4 px-4 text-center text-gray-500">
                                        No hay membresías registradas
                                    </td>
                                </tr>
                                <tr v-for="membresia in membresias" :key="membresia.id" class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ membresia.nombre }}</td>
                                    <td class="py-3 px-4">
                                        {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(membresia.precio) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(membresia.comision_directa) }}
                                    </td>
                                    <td class="py-3 px-4">{{ membresia.porcentaje_rendimiento }}%</td>
                                    <td class="py-3 px-4">{{ membresia.porcentaje_comision_sponsor }}%</td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                as="a" 
                                                :href="route('admin.memberships.edit', { membership: membresia.id })"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                <span class="sr-only">Editar</span>
                                            </Button>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                @click="confirmDelete(membresia)"
                                            >
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                                <span class="sr-only">Eliminar</span>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
        
        <!-- Diálogo de confirmación para eliminar -->
        <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>¿Estás seguro de eliminar esta membresía?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Si hay usuarios con esta membresía, la eliminación no se realizará.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="flex justify-end space-x-2">
                    <Button variant="outline" @click="showDeleteDialog = false">Cancelar</Button>
                    <Button variant="destructive" @click="deleteMembresia">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template> 