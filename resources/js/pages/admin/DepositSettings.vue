<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';

interface Props {
    depositSettings: {
        min_amount: number;
        max_amount: number;
    };
}

const props = defineProps<Props>();

// Formulario para actualizar la configuración
const form = useForm({
    min_amount: props.depositSettings.min_amount,
    max_amount: props.depositSettings.max_amount,
});

// Función para guardar los cambios
const saveSettings = () => {
    form.post(route('admin.settings.deposits.update'), {
        onSuccess: () => {
            alert('Configuración guardada correctamente');
        }
    });
};

// Función para restablecer la configuración
const resetSettings = () => {
    if (confirm('¿Está seguro de restablecer la configuración a valores predeterminados?')) {
        useForm({}).post(route('admin.settings.deposits.reset'), {
            onSuccess: () => {
                window.location.reload();
            }
        });
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de Administración',
        href: route('dashboard'),
    },
    {
        title: 'Configuración de Depósitos',
        href: route('admin.settings.deposits'),
    },
];
</script>

<template>
    <Head title="Configuración de Depósitos" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Configuración de Montos de Depósito</CardTitle>
                    <CardDescription>
                        Configure los montos mínimo y máximo permitidos para las solicitudes de depósito.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="saveSettings">
                        <!-- Montos mínimos y máximos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <Label for="min-amount">Monto mínimo de depósito ($)</Label>
                                <Input 
                                    id="min-amount" 
                                    v-model="form.min_amount" 
                                    type="number" 
                                    min="1" 
                                    step="1" 
                                    class="mt-1" 
                                />
                                <div v-if="form.errors.min_amount" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.min_amount }}
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    El monto mínimo que los usuarios podrán depositar.
                                </p>
                            </div>
                            
                            <div>
                                <Label for="max-amount">Monto máximo de depósito ($)</Label>
                                <Input 
                                    id="max-amount" 
                                    v-model="form.max_amount" 
                                    type="number" 
                                    min="1" 
                                    step="1" 
                                    class="mt-1" 
                                />
                                <div v-if="form.errors.max_amount" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.max_amount }}
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    El monto máximo que los usuarios podrán depositar en una sola transacción.
                                </p>
                            </div>
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex justify-between">
                    <Button variant="outline" type="button" @click="resetSettings">
                        Restablecer valores predeterminados
                    </Button>
                    <Button type="button" @click="saveSettings" :disabled="form.processing">
                        Guardar configuración
                    </Button>
                </CardFooter>
            </Card>
            
            <Alert class="mb-6">
                <AlertTitle>Información importante</AlertTitle>
                <AlertDescription>
                    <p>Los cambios en esta configuración afectarán inmediatamente a todas las solicitudes de depósito nuevas.</p>
                    <ul class="list-disc pl-5 mt-2">
                        <li>El monto mínimo debe ser un valor positivo mayor a cero.</li>
                        <li>El monto máximo debe ser mayor que el monto mínimo.</li>
                        <li>Estas limitaciones se aplican a todas las nuevas solicitudes de depósito.</li>
                        <li>Los depósitos existentes no se verán afectados por estos cambios.</li>
                    </ul>
                </AlertDescription>
            </Alert>
        </div>
    </AppLayout>
</template> 