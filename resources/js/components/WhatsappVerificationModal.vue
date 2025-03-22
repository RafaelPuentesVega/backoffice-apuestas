<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black opacity-50" @click="$emit('close')"></div>
        
        <!-- Modal -->
        <div class="relative z-10 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div class="absolute right-4 top-4">
                <button 
                    type="button" 
                    class="text-gray-400 hover:text-gray-500"
                    @click="$emit('close')"
                >
                    <span class="sr-only">Cerrar</span>
                    <X class="h-5 w-5" />
                </button>
            </div>
            
            <div class="mb-5 text-center">
                <h3 class="text-lg font-medium text-gray-900">Verificación de WhatsApp</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Ingresa el código de verificación enviado a tu número de WhatsApp
                </p>
            </div>
            
            <!-- Mensajes de error y éxito -->
            <div v-if="errorMessage" class="mb-4 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <AlertCircle class="h-5 w-5 text-red-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
                    </div>
                </div>
            </div>
            
            <div v-if="successMessage" class="mb-4 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircle class="h-5 w-5 text-green-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                    </div>
                </div>
            </div>
            
            <form @submit.prevent="verifyCode">
                <div class="mb-4">
                    <Label for="verification_code">Código de Verificación</Label>
                    <Input 
                        id="verification_code" 
                        v-model="form.code" 
                        type="text" 
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        class="mt-1 block w-full" 
                        placeholder="123456"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.code" class="mt-2" />
                </div>
                
                <div class="mt-6 flex justify-between">
                    <Button 
                        type="button" 
                        variant="outline" 
                        @click="resendCode"
                        :disabled="loading"
                    >
                        Reenviar Código
                    </Button>
                    <Button 
                        type="submit"
                        :disabled="loading || !form.code"
                    >
                        <LoaderCircle v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                        Verificar
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { AlertCircle, CheckCircle, LoaderCircle, X } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    countryCode: String,
    phone: String,
    show: Boolean
});

const emit = defineEmits(['close', 'verified']);

const form = useForm({
    code: ''
});

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Verificar el código
const verifyCode = () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    axios.post(route('api.whatsapp.verify-code'), {
        code: form.code
    })
    .then(response => {
        if (response.data.success) {
            successMessage.value = response.data.message || 'Código verificado correctamente';
            
            // Esperar un momento para que el usuario vea el mensaje de éxito
            setTimeout(() => {
                emit('verified', form.code);
            }, 1500);
        } else {
            errorMessage.value = response.data.message || 'El código no es válido';
        }
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.message || 'Error al verificar el código';
    })
    .finally(() => {
        loading.value = false;
    });
};

// Reenviar el código
const resendCode = () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    axios.post(route('api.whatsapp.request-verification'), {
        country_code: props.countryCode,
        phone: props.phone
    })
    .then(response => {
        if (response.data.success) {
            successMessage.value = 'Código reenviado correctamente';
        } else {
            errorMessage.value = response.data.message || 'Error al reenviar el código';
        }
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.message || 'Error al reenviar el código';
    })
    .finally(() => {
        loading.value = false;
    });
};
</script>
