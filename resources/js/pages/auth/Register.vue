<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { countries } from '@/models/countries'; 
import DualVerificationModal from '@/components/DualVerificationModal.vue';
import axios from 'axios';

// Obtener parámetros de la URL con Inertia.js
const page = usePage();
const sponsorId = page.props.sponsor || '';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '', 
    sponsor_id: '',
    country_code: '+57',
});

const showVerificationModal = ref(false);
const isVerified = ref(false);
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Esta función envía códigos de verificación
const sendVerificationCode = () => {
    loading.value = true;
    errorMessage.value = '';
    
    axios.post(route('send.verification'), {
        email: form.email,
        country_code: form.country_code,
        phone: form.phone,
        sponsor_id: form.sponsor_id
    })
    .then(response => {
        if (response.data.success) {
            showVerificationModal.value = true;
            successMessage.value = response.data.message;
        } else {
            errorMessage.value = response.data.message;
        }
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.message || 'Error al enviar los códigos de verificación';
        console.error('Error al enviar códigos de verificación:', error);
    })
    .finally(() => {
        loading.value = false;
    });
};

// Esta función envía el formulario final de registro
const submitRegistration = () => {
    loading.value = true;
    
    form.post(route('register'), {
        onSuccess: () => {
            // El redireccionamiento lo maneja el controlador
        },
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] || 'Error al registrar la cuenta';
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

// Elegir qué función llamar según el estado de verificación
const submit = () => {
    if (!isVerified.value) {
        sendVerificationCode();
    } else {
        submitRegistration();
    }
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    form.sponsor_id = urlParams.get('sponsor') || '';
});

const validatePhone = () => {
    form.phone = form.phone.replace(/\D/g, '').slice(0, 10); 
};

const closeModal = () => {
    showVerificationModal.value = false;
};

// Cuando se confirma la verificación, actualizar el formulario y el estado
const confirmRegistration = () => {
    isVerified.value = true;
    showVerificationModal.value = false;
    successMessage.value = 'Verificación completada. Registrando cuenta...';
    
    // Enviar el formulario automáticamente después de la verificación
    // Solo si no se ha enviado ya
    if (!loading.value) {
        submitRegistration();
    }
};

</script>

<template>
    <AuthBase title="Crear una cuenta" description="Ingresa tus datos para crear tu cuenta">
        <Head title="Registro" />

        <!-- Mensajes de error y éxito -->
        <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ errorMessage }}
        </div>
        
        <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ successMessage }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Campos del formulario -->
                <div class="grid gap-2">
                    <Label for="name">Nombres</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="nombres" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Contraseña</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar Contraseña</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Número de Teléfono</Label>
                    <div class="flex items-center gap-2">
                        <select id="country_code" :tabindex="5" v-model="form.country_code" class="border p-2 rounded">
                            <option v-for="country in countries" :key="country.code" :value="country.code">
                                {{ country.name }} ({{ country.code }})
                            </option>
                        </select>
                        <Input
                            id="phone"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            required
                            @input="validatePhone"
                            :tabindex="6"
                            v-model="form.phone"
                            maxlength="12"
                            placeholder="Número de Teléfono"
                        />
                    </div>
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="sponsor_id">Sponsor</Label>
                    <Input 
                        id="sponsor_id" 
                        type="text" 
                        v-model="form.sponsor_id" 
                        readonly 
                        :tabindex="7"
                        placeholder="No sponsor" 
                        class="bg-gray-100 cursor-not-allowed" 
                    />
                    <InputError :message="form.errors.sponsor_id" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="8" :disabled="loading">
                    <LoaderCircle v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                    {{ isVerified ? 'Crear Cuenta' : 'Verificar Datos' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                ¿Ya tienes una cuenta?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="9">Iniciar sesión</TextLink>
            </div>
        </form>
        
        <!-- Modal de verificación dual -->
        <DualVerificationModal 
            :email="form.email" 
            :phone="form.phone"
            :country-code="form.country_code"
            :show="showVerificationModal" 
            @close="closeModal" 
            @confirmed="confirmRegistration" 
        />
    </AuthBase>
</template>