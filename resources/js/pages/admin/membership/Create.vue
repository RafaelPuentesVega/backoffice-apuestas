<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administración',
        href: '/admin',
    },
    {
        title: 'Membresías',
        href: route('admin.memberships.index'),
    },
    {
        title: 'Crear Membresía',
        href: route('admin.memberships.create'),
    },
];

const form = useForm({
    nombre: '',
    precio: 0,
    comision_directa: 0,
    porcentaje_rendimiento: 0,
    porcentaje_comision_sponsor: 0,
});

const submit = () => {
    form.post(route('admin.memberships.store'));
};
</script>

<template>
    <Head title="Crear Membresía" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <Card class="max-w-2xl mx-auto">
                <CardHeader>
                    <CardTitle>Crear Nueva Membresía</CardTitle>
                    <CardDescription>
                        Complete el formulario para crear una nueva membresía en el sistema.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="nombre">Nombre</Label>
                            <Input 
                                id="nombre" 
                                v-model="form.nombre" 
                                type="text" 
                                placeholder="Nombre de la membresía"
                                class="mt-1"
                                required
                            />
                            <div v-if="form.errors.nombre" class="text-sm text-red-500 mt-1">
                                {{ form.errors.nombre }}
                            </div>
                        </div>
                        
                        <div>
                            <Label for="precio">Precio ($)</Label>
                            <Input 
                                id="precio" 
                                v-model="form.precio" 
                                type="number" 
                                min="0" 
                                step="0.01"
                                placeholder="Precio de la membresía"
                                class="mt-1" 
                                required
                            />
                            <div v-if="form.errors.precio" class="text-sm text-red-500 mt-1">
                                {{ form.errors.precio }}
                            </div>
                        </div>
                        
                        <div>
                            <Label for="comision_directa">Comisión Directa ($)</Label>
                            <Input 
                                id="comision_directa" 
                                v-model="form.comision_directa" 
                                type="number" 
                                min="0" 
                                step="0.01"
                                placeholder="Comisión directa para el sponsor"
                                class="mt-1" 
                                required
                            />
                            <div class="text-xs text-gray-500 mt-1">
                                Monto fijo que recibe el sponsor cuando un referido adquiere esta membresía.
                            </div>
                            <div v-if="form.errors.comision_directa" class="text-sm text-red-500 mt-1">
                                {{ form.errors.comision_directa }}
                            </div>
                        </div>
                        
                        <div>
                            <Label for="porcentaje_rendimiento">Porcentaje de Rendimiento (%)</Label>
                            <Input 
                                id="porcentaje_rendimiento" 
                                v-model="form.porcentaje_rendimiento" 
                                type="number" 
                                min="0" 
                                max="100" 
                                step="0.01"
                                placeholder="Porcentaje de rendimiento"
                                class="mt-1" 
                                required
                            />
                            <div class="text-xs text-gray-500 mt-1">
                                Porcentaje de rendimiento que gana el usuario con esta membresía.
                            </div>
                            <div v-if="form.errors.porcentaje_rendimiento" class="text-sm text-red-500 mt-1">
                                {{ form.errors.porcentaje_rendimiento }}
                            </div>
                        </div>
                        
                        <div>
                            <Label for="porcentaje_comision_sponsor">Porcentaje de Comisión al Sponsor (%)</Label>
                            <Input 
                                id="porcentaje_comision_sponsor" 
                                v-model="form.porcentaje_comision_sponsor" 
                                type="number" 
                                min="0" 
                                max="100" 
                                step="0.01"
                                placeholder="Porcentaje de comisión para el sponsor"
                                class="mt-1" 
                                required
                            />
                            <div class="text-xs text-gray-500 mt-1">
                                Porcentaje del rendimiento que se otorga al sponsor.
                            </div>
                            <div v-if="form.errors.porcentaje_comision_sponsor" class="text-sm text-red-500 mt-1">
                                {{ form.errors.porcentaje_comision_sponsor }}
                            </div>
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex justify-between">
                    <Button 
                        variant="outline" 
                        type="button" 
                        :href="route('admin.memberships.index')"
                        as="a"
                    >
                        Cancelar
                    </Button>
                    <Button 
                        type="button" 
                        @click="submit" 
                        :disabled="form.processing"
                    >
                        Crear Membresía
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template> 