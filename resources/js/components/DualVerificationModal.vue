<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { LoaderCircle, Mail, Phone } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    email: String,
    phone: String,
    countryCode: String,
    show: Boolean
});

const emit = defineEmits(['close', 'confirmed']);

// Estado de verificación
const emailVerified = ref(false);
const whatsappVerified = ref(false);
const activeTab = ref('email'); // 'email' o 'whatsapp'
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Variable para controlar si ya se ha emitido el evento confirmed
const hasEmittedConfirmation = ref(false);

// Formularios para cada verificación
const emailForm = useForm({
    email: '',
    code: ''
});

const whatsappForm = useForm({
    code: ''
});

// Observar cambios en props y actualizar los formularios
watch(() => props.email, (newEmail) => {
    emailForm.email = newEmail;
}, { immediate: true });

// Limpiar mensajes cuando cambia el tab
watch(activeTab, () => {
    errorMessage.value = '';
    successMessage.value = '';
});

// Observar si ambos están verificados para emitir confirmación automáticamente
watch([emailVerified, whatsappVerified], ([newEmailVerified, newWhatsappVerified]) => {
    if (newEmailVerified && newWhatsappVerified) {
        successMessage.value = 'Ambos códigos verificados correctamente. Procesando registro...';
        // Emitir el evento solo una vez
        if (!hasEmittedConfirmation.value) {
            hasEmittedConfirmation.value = true;
            setTimeout(() => {
                emit('confirmed');
            }, 1500); // Dar tiempo para que el usuario vea el mensaje de éxito
        }
    }
}, { immediate: true });

// Verificar código de email
const verifyEmailCode = () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    axios.post(route('verify.email.code'), {
        email: emailForm.email,
        code: emailForm.code
    })
    .then(response => {
        if (response.data.success) {
            emailVerified.value = true;
            successMessage.value = response.data.message;
            
            // Si el WhatsApp aún no está verificado, cambiar al tab de WhatsApp
            if (!whatsappVerified.value) {
                activeTab.value = 'whatsapp';
            }
        } else {
            errorMessage.value = response.data.message;
        }
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.message || 'Error al verificar el código de correo';
    })
    .finally(() => {
        loading.value = false;
    });
};

// Verificar código de WhatsApp
const verifyWhatsappCode = () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    axios.post(route('verify.whatsapp.code'), {
        code: whatsappForm.code
    })
    .then(response => {
        if (response.data.success) {
            whatsappVerified.value = true;
            successMessage.value = response.data.message;
            
            // Si el email aún no está verificado, cambiar al tab de email
            if (!emailVerified.value) {
                activeTab.value = 'email';
            }
        } else {
            errorMessage.value = response.data.message;
        }
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.message || 'Error al verificar el código de WhatsApp';
    })
    .finally(() => {
        loading.value = false;
    });
};

// Reenviar códigos
const resendVerificationCode = () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    axios.post(route('send.verification'), {
        email: props.email,
        country_code: props.countryCode,
        phone: props.phone
    })
    .then(response => {
        if (response.data.success) {
            successMessage.value = 'Códigos reenviados correctamente';
        } else {
            errorMessage.value = response.data.message;
        }
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.message || 'Error al reenviar los códigos';
    })
    .finally(() => {
        loading.value = false;
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96 max-w-full mx-4">
            <h2 class="text-lg font-semibold mb-4">Verificación de Cuenta</h2>
            <p class="text-sm text-gray-600 mb-4">
                Para completar tu registro, debes verificar tanto tu correo electrónico como tu número de WhatsApp.
            </p>
            
            <!-- Tabs de navegación -->
            <div class="flex border-b mb-4">
                <button 
                    @click="activeTab = 'email'" 
                    class="py-2 px-4 flex items-center gap-2"
                    :class="activeTab === 'email' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'"
                >
                    <Mail class="h-4 w-4" />
                    <span>Email</span>
                    <span v-if="emailVerified" class="ml-1 text-green-500 text-xs">(Verificado)</span>
                </button>
                <button 
                    @click="activeTab = 'whatsapp'" 
                    class="py-2 px-4 flex items-center gap-2"
                    :class="activeTab === 'whatsapp' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'"
                >
                    <Phone class="h-4 w-4" />
                    <span>WhatsApp</span>
                    <span v-if="whatsappVerified" class="ml-1 text-green-500 text-xs">(Verificado)</span>
                </button>
            </div>
            
            <!-- Mensajes de error y éxito -->
            <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
                {{ errorMessage }}
            </div>
            
            <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                {{ successMessage }}
            </div>
            
            <!-- Tab de Email -->
            <div v-if="activeTab === 'email'">
                <p class="text-sm text-gray-600 mb-4">
                    Ingresa el código de 6 dígitos que enviamos a <strong>{{ email }}</strong>
                </p>
                
                <form @submit.prevent="verifyEmailCode">
                    <div class="grid gap-2">
                        <Label for="email-code">Código de Verificación</Label>
                        <Input 
                            id="email-code" 
                            type="text" 
                            required 
                            v-model="emailForm.code" 
                            placeholder="123456" 
                            :disabled="emailVerified || loading"
                            maxlength="6"
                        />
                        <InputError :message="emailForm.errors.code" />
                    </div>
                    
                    <div class="flex justify-between mt-4">
                        <Button 
                            type="button" 
                            variant="outline" 
                            @click="resendVerificationCode" 
                            :disabled="loading"
                        >
                            Reenviar
                        </Button>
                        <Button 
                            type="submit" 
                            :disabled="emailVerified || loading || !emailForm.code"
                        >
                            <LoaderCircle v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                            {{ emailVerified ? 'Verificado' : 'Verificar Email' }}
                        </Button>
                    </div>
                </form>
            </div>
            
            <!-- Tab de WhatsApp -->
            <div v-if="activeTab === 'whatsapp'">
                <p class="text-sm text-gray-600 mb-4">
                    Ingresa el código de 6 dígitos que enviamos a tu WhatsApp <strong>{{ countryCode }} {{ phone }}</strong>
                </p>
                
                <form @submit.prevent="verifyWhatsappCode">
                    <div class="grid gap-2">
                        <Label for="whatsapp-code">Código de Verificación</Label>
                        <Input 
                            id="whatsapp-code" 
                            type="text" 
                            required 
                            v-model="whatsappForm.code" 
                            placeholder="123456" 
                            :disabled="whatsappVerified || loading"
                            maxlength="6"
                        />
                        <InputError :message="whatsappForm.errors.code" />
                    </div>
                    
                    <div class="flex justify-between mt-4">
                        <Button 
                            type="button" 
                            variant="outline" 
                            @click="resendVerificationCode" 
                            :disabled="loading"
                        >
                            Reenviar
                        </Button>
                        <Button 
                            type="submit" 
                            :disabled="whatsappVerified || loading || !whatsappForm.code"
                        >
                            <LoaderCircle v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                            {{ whatsappVerified ? 'Verificado' : 'Verificar WhatsApp' }}
                        </Button>
                    </div>
                </form>
            </div>
            
            <!-- Botones de acción -->
            <div class="flex justify-between mt-6">
                <Button type="button" variant="outline" @click="emit('close')">Cancelar</Button>
                <Button 
                    type="button" 
                    @click="emit('confirmed')" 
                    :disabled="!emailVerified || !whatsappVerified"
                >
                    Continuar
                </Button>
            </div>
        </div>
    </div>
</template>
