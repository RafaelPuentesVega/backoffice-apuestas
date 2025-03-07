<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';

// Formulario para solicitud de retiro
const form = useForm({
    amount: '',
    account_type: '',
    account_number: '',
    bank: '',
    description: ''
});

// Lista de bancos para el select
const banks = [
    { id: 1, name: 'Banco Nación' },
    { id: 2, name: 'Banco Galicia' },
    { id: 3, name: 'Banco Santander' },
    { id: 4, name: 'Banco BBVA' },
    { id: 5, name: 'Banco Macro' },
    { id: 6, name: 'Banco HSBC' }
];

// Lista de tipos de cuenta
const accountTypes = [
    { id: 'savings', name: 'Caja de Ahorro' },
    { id: 'checking', name: 'Cuenta Corriente' },
    { id: 'virtual', name: 'Cuenta Virtual' }
];

// Manejar el envío del formulario
const submitForm = () => {
    form.post(route('withdrawal.store'), {
        onSuccess: () => {
            // El redireccionamiento se maneja en el controlador
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Solicitar Retiro" />
        
        <div class="container mx-auto py-8">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-2xl font-bold mb-6">Solicitar Retiro</h1>
                
                <Card>
                    <CardHeader>
                        <CardTitle>Formulario de Retiro</CardTitle>
                        <CardDescription>
                            Complete los datos para solicitar un retiro de fondos a su cuenta bancaria.
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent>
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="amount">Monto a retirar</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                    <Input 
                                        id="amount" 
                                        v-model="form.amount" 
                                        type="number" 
                                        placeholder="0.00" 
                                        class="pl-8"
                                        :class="{ 'border-red-500': form.errors.amount }"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.amount" class="text-sm text-red-500">{{ form.errors.amount }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="bank">Banco</Label>
                                <Select v-model="form.bank" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.bank }">
                                        <SelectValue placeholder="Seleccione un banco" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="bank in banks" :key="bank.id" :value="bank.id">
                                            {{ bank.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.bank" class="text-sm text-red-500">{{ form.errors.bank }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="account_type">Tipo de cuenta</Label>
                                <Select v-model="form.account_type" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.account_type }">
                                        <SelectValue placeholder="Seleccione tipo de cuenta" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in accountTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.account_type" class="text-sm text-red-500">{{ form.errors.account_type }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="account_number">Número de cuenta</Label>
                                <Input 
                                    id="account_number" 
                                    v-model="form.account_number" 
                                    type="text" 
                                    placeholder="Ingrese el número de cuenta" 
                                    :class="{ 'border-red-500': form.errors.account_number }"
                                    required
                                />
                                <p v-if="form.errors.account_number" class="text-sm text-red-500">{{ form.errors.account_number }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="description">Descripción (opcional)</Label>
                                <Textarea 
                                    id="description" 
                                    v-model="form.description" 
                                    placeholder="Agregue información adicional si es necesario"
                                    :class="{ 'border-red-500': form.errors.description }"
                                />
                                <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                            </div>
                            
                            <Alert v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border-red-200">
                                <AlertCircle class="h-4 w-4 text-red-500" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>
                                    Por favor corrija los errores en el formulario antes de continuar.
                                </AlertDescription>
                            </Alert>
                        </form>
                    </CardContent>
                    
                    <CardFooter class="flex justify-between">
                        <Button variant="outline" :href="route('withdrawal.history')" as="a">Cancelar</Button>
                        <Button type="submit" @click="submitForm" :disabled="form.processing">
                            {{ form.processing ? 'Procesando...' : 'Solicitar Retiro' }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 