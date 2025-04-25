<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/card/Card.vue';
import CardHeader from '@/Components/ui/card/CardHeader.vue';
import CardTitle from '@/Components/ui/card/CardTitle.vue';
import CardContent from '@/Components/ui/card/CardContent.vue';
import CardFooter from '@/Components/ui/card/CardFooter.vue';
import Button from '@/Components/ui/button/Button.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/Components/ui/label/Label.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Toast } from '@/Components/ui/toast';

interface Props {
    hasPendingDeposit: boolean;
    depositLimits: {
        min: number;
        max: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: route('dashboard') },
    { label: 'Depósitos', href: route('deposit.create') }
];

const form = useForm({
    amount: '',
    balance_type: 'capital',
    bank_name: '',
    transaction_reference: '',
    receipt_image: null as File | null,
    notes: '',
});

const previewImage = ref<string | null>(null);
const fileError = ref<string | null>(null);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    fileError.value = null;
    
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        
        // Validar tipo de archivo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            fileError.value = 'El archivo debe ser una imagen (JPEG, PNG, GIF)';
            form.receipt_image = null;
            previewImage.value = null;
            return;
        }
        
        // Validar tamaño (máximo 2MB)
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
            fileError.value = 'La imagen no debe exceder los 2MB';
            form.receipt_image = null;
            previewImage.value = null;
            return;
        }
        
        form.receipt_image = file;
        
        // Crear vista previa
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function submit() {
    form.post(route('deposit.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Toast.success({ title: 'Depósito enviado', description: 'Su solicitud de depósito ha sido enviada correctamente' });
        },
        onError: () => {
            Toast.error({ title: 'Error', description: 'Hubo un error al procesar su solicitud. Intente de nuevo.' });
        }
    });
}
</script>

<template>
    <Head title="Nuevo Depósito" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem v-for="(item, index) in breadcrumbs" :key="index">
                                <a v-if="index < breadcrumbs.length - 1" :href="item.href" class="text-gray-500 hover:text-gray-700">
                                    {{ item.label }}
                                </a>
                                <span v-else class="font-medium text-gray-900">{{ item.label }}</span>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>

                <h1 class="text-2xl font-semibold text-gray-900 mb-6">Solicitar Depósito</h1>

                <div v-if="hasPendingDeposit" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Ya tiene una solicitud de depósito pendiente. Debe esperar a que se procese antes de crear una nueva solicitud.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <Card>
                        <CardHeader>
                            <CardTitle>Formulario de Depósito</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Monto -->
                                <div>
                                    <Label for="amount">Monto ($)</Label>
                                    <Input 
                                        id="amount" 
                                        v-model="form.amount" 
                                        type="number" 
                                        step="0.01"
                                        min="1"
                                        :min="props.depositLimits.min"
                                        :max="props.depositLimits.max"
                                        required
                                        class="mt-1 block w-full"
                                        :class="{ 'border-red-500': form.errors.amount }"
                                    />
                                    <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Monto mínimo: ${{ props.depositLimits.min }}, Monto máximo: ${{ props.depositLimits.max }}
                                    </p>
                                </div>

                                <!-- Hidden input para tipo de saldo (siempre capital) -->
                                <input type="hidden" v-model="form.balance_type" />

                                <!-- Banco -->
                                <div>
                                    <Label for="bank_name">Banco o entidad financiera</Label>
                                    <Input 
                                        id="bank_name" 
                                        v-model="form.bank_name" 
                                        type="text" 
                                        required
                                        class="mt-1 block w-full"
                                        :class="{ 'border-red-500': form.errors.bank_name }"
                                    />
                                    <p v-if="form.errors.bank_name" class="mt-1 text-sm text-red-600">{{ form.errors.bank_name }}</p>
                                </div>

                                <!-- Referencia de transacción -->
                                <div>
                                    <Label for="transaction_reference">Referencia de transacción</Label>
                                    <Input 
                                        id="transaction_reference" 
                                        v-model="form.transaction_reference" 
                                        type="text" 
                                        required
                                        class="mt-1 block w-full"
                                        :class="{ 'border-red-500': form.errors.transaction_reference }"
                                    />
                                    <p v-if="form.errors.transaction_reference" class="mt-1 text-sm text-red-600">{{ form.errors.transaction_reference }}</p>
                                </div>

                                <!-- Comprobante -->
                                <div>
                                    <Label for="receipt_image">Comprobante de depósito</Label>
                                    <div class="mt-1 flex items-center justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md"
                                        :class="{ 'border-red-500': form.errors.receipt_image || fileError }">
                                        <div class="space-y-1 text-center">
                                            <svg v-if="!previewImage" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4h-8m-12 0a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div v-if="previewImage" class="flex justify-center">
                                                <img :src="previewImage" alt="Vista previa del comprobante" class="max-h-64 rounded" />
                                            </div>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="receipt_image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                    <span v-if="!previewImage">Seleccionar archivo</span>
                                                    <span v-else>Cambiar archivo</span>
                                                    <Input 
                                                        id="receipt_image" 
                                                        type="file" 
                                                        accept="image/*"
                                                        @change="onFileChange"
                                                        required
                                                        class="sr-only"
                                                    />
                                                </label>
                                                <p v-if="!previewImage" class="pl-1">o arrastre y suelte</p>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                PNG, JPG, GIF hasta 2MB
                                            </p>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.receipt_image" class="mt-1 text-sm text-red-600">{{ form.errors.receipt_image }}</p>
                                    <p v-if="fileError" class="mt-1 text-sm text-red-600">{{ fileError }}</p>
                                </div>

                                <!-- Notas adicionales -->
                                <div>
                                    <Label for="notes">Notas adicionales (opcional)</Label>
                                    <Textarea 
                                        id="notes" 
                                        v-model="form.notes" 
                                        rows="3"
                                        class="mt-1 block w-full"
                                        :class="{ 'border-red-500': form.errors.notes }"
                                    ></Textarea>
                                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                                </div>
                            </form>
                        </CardContent>
                        <CardFooter class="flex justify-end">
                            <Button 
                                type="submit" 
                                @click="submit" 
                                :disabled="form.processing"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                            </Button>
                        </CardFooter>
                    </Card>

                    <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="font-medium text-gray-900">Instrucciones</h3>
                        <ul class="mt-2 list-disc pl-5 space-y-1 text-sm text-gray-600">
                            <li>Ingrese el monto que desea depositar a su cuenta de capital.</li>
                            <li>Realice la transferencia o depósito a nuestra cuenta bancaria.</li>
                            <li>Tome una captura o foto del comprobante de la transferencia.</li>
                            <li>Suba el comprobante de pago en el formulario.</li>
                            <li>Su solicitud será revisada y aprobada por un administrador.</li>
                            <li>Una vez aprobada, el monto se acreditará en su saldo de capital.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 