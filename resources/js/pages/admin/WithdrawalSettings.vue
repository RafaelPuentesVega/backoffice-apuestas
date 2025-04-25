<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';

interface Props {
    withdrawalSettings: {
        capital_days: number[];
        earnings_days: number[];
        network_days: number[];
        min_amount: number;
        max_amount: number;
    };
}

const props = defineProps<Props>();

// Mapa de días de la semana
const weekDays = [
    { value: 0, label: 'Domingo' },
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
];

// Formulario para actualizar la configuración
const form = useForm({
    capital_days: props.withdrawalSettings.capital_days,
    earnings_days: props.withdrawalSettings.earnings_days,
    network_days: props.withdrawalSettings.network_days,
    min_amount: props.withdrawalSettings.min_amount,
    max_amount: props.withdrawalSettings.max_amount,
});

// Verificar si un día está seleccionado
const isDaySelected = (dayValue: number, balanceType: 'capital_days' | 'earnings_days' | 'network_days') => {
    return form[balanceType].includes(dayValue);
};

// Manejar cambio en selección de día
const toggleDay = (dayValue: number, balanceType: 'capital_days' | 'earnings_days' | 'network_days') => {
    const index = form[balanceType].indexOf(dayValue);
    if (index === -1) {
        form[balanceType].push(dayValue);
    } else {
        form[balanceType].splice(index, 1);
    }
};

// Función para guardar los cambios
const saveSettings = () => {
    form.post(route('admin.settings.withdrawals.update'), {
        onSuccess: () => {
            alert('Configuración guardada correctamente');
        }
    });
};

// Función para restablecer la configuración
const resetSettings = () => {
    if (confirm('¿Está seguro de restablecer la configuración a valores predeterminados?')) {
        useForm({}).post(route('admin.settings.withdrawals.reset'), {
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
        title: 'Configuración de Retiros',
        href: route('admin.settings.withdrawals'),
    },
];
</script>

<template>
    <Head title="Configuración de Retiros" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Configuración de Días de Retiro</CardTitle>
                    <CardDescription>
                        Configure los días de la semana en los que se permitirá realizar retiros para cada tipo de saldo.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="saveSettings">
                        <!-- Capital -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">Días para retiro de Capital</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div v-for="day in weekDays" :key="day.value" class="flex items-center space-x-2">
                                    <Checkbox 
                                        :id="`capital-${day.value}`" 
                                        :checked="isDaySelected(day.value, 'capital_days')"
                                        @update:checked="() => toggleDay(day.value, 'capital_days')" 
                                    />
                                    <Label :for="`capital-${day.value}`">{{ day.label }}</Label>
                                </div>
                            </div>
                        </div>
                        
                        <Separator class="my-6" />
                        
                        <!-- Ganancias -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">Días para retiro de Ganancias</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div v-for="day in weekDays" :key="day.value" class="flex items-center space-x-2">
                                    <Checkbox 
                                        :id="`earnings-${day.value}`" 
                                        :checked="isDaySelected(day.value, 'earnings_days')"
                                        @update:checked="() => toggleDay(day.value, 'earnings_days')" 
                                    />
                                    <Label :for="`earnings-${day.value}`">{{ day.label }}</Label>
                                </div>
                            </div>
                        </div>
                        
                        <Separator class="my-6" />
                        
                        <!-- Comisiones de Red -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">Días para retiro de Comisiones de Red</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div v-for="day in weekDays" :key="day.value" class="flex items-center space-x-2">
                                    <Checkbox 
                                        :id="`network-${day.value}`" 
                                        :checked="isDaySelected(day.value, 'network_days')"
                                        @update:checked="() => toggleDay(day.value, 'network_days')" 
                                    />
                                    <Label :for="`network-${day.value}`">{{ day.label }}</Label>
                                </div>
                            </div>
                        </div>
                        
                        <Separator class="my-6" />
                        
                        <!-- Montos mínimos y máximos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <Label for="min-amount">Monto mínimo de retiro ($)</Label>
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
                            </div>
                            
                            <div>
                                <Label for="max-amount">Monto máximo de retiro ($)</Label>
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
                    <p>Los cambios en esta configuración afectarán inmediatamente a todas las solicitudes de retiro.</p>
                    <ul class="list-disc pl-5 mt-2">
                        <li>Si un día no está marcado, los usuarios no podrán solicitar retiros de ese tipo de saldo en ese día.</li>
                        <li>Los montos mínimo y máximo se aplican a todas las solicitudes de retiro, independientemente del tipo de saldo.</li>
                        <li>Se recomienda mantener al menos un día habilitado para cada tipo de saldo.</li>
                    </ul>
                </AlertDescription>
            </Alert>
        </div>
    </AppLayout>
</template> 