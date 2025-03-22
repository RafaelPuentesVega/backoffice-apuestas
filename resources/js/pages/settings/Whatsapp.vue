<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Head, usePage } from '@inertiajs/vue3';
import { TransitionRoot } from '@headlessui/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, CheckCircle, X } from 'lucide-vue-next';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import WhatsappVerificationModal from '@/components/WhatsappVerificationModal.vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    whatsapp: Array,
    whatsappHistory: Array,
    success: String,
    error: String
});

const form = useForm({
    
    country_code:  '+57',
    phone:  '',
    verification_code: ''
});

const showVerificationModal = ref(false);
const showVerificationField = ref(false);
const successMessage = ref(props.success || null);
const errorMessage = ref(props.error || null);
const debugLoading = ref(false);

const hasHistory = computed(() => {
    return props.whatsappHistory && props.whatsappHistory.length > 0;
});

// Solicitar código de verificación
const requestVerificationCode = () => {
    axios.post(route('api.whatsapp.request-verification'), {
        country_code: form.country_code,
        phone: form.phone
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
        errorMessage.value = error.response?.data?.message || 'Error al solicitar el código de verificación';
    });
};

// Actualizar número de WhatsApp
const submit = () => {
    form.post(route('settings.whatsapp.update'), {
        onSuccess: () => {
            showVerificationField.value = false;
            form.reset('verification_code');
            successMessage.value = 'Número de WhatsApp actualizado correctamente';
        },
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] || 'Error al actualizar el número de WhatsApp';
        }
    });
};

// Verificar y reparar historial
const checkAndRepairHistory = () => {
    debugLoading.value = true;
    
    axios.get(route('api.whatsapp.check-history'))
        .then(response => {
            if (response.data.success) {
                successMessage.value = 'Historial verificado y reparado correctamente';
                // Recargar la página para mostrar los cambios
                window.location.reload();
            } else {
                errorMessage.value = response.data.message || 'Error al verificar el historial';
            }
        })
        .catch(error => {
            errorMessage.value = error.response?.data?.message || 'Error al verificar el historial';
        })
        .finally(() => {
            debugLoading.value = false;
        });
};

// Formatear fecha
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Cuando se verifica el código
const onVerified = (code) => {
    form.verification_code = code;
    showVerificationModal.value = false;
    // Enviar el formulario automáticamente
    submit();
};

// Cerrar el modal
const closeModal = () => {
    showVerificationModal.value = false;
};
</script>

<template>
<AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Configuración de WhatsApp" />
    
    <SettingsLayout>
        <div class="space-y-6">
            <!-- Formulario de actualización de WhatsApp -->
            <Card>
                <CardHeader>
                    <CardTitle>WhatsApp</CardTitle>
                    <CardDescription>
                        Actualiza tu número de WhatsApp para recibir notificaciones importantes.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <div class="grid w-full items-center gap-4">
                            <div class="flex flex-col space-y-1.5">
                                <Label for="country_code">Código de País</Label>
                                <Input id="country_code" v-model="form.country_code" placeholder="+57" />
                                <InputError :message="form.errors.country_code" class="mt-2" />
                            </div>
                            <div class="flex flex-col space-y-1.5">
                                <Label for="phone">Número de WhatsApp</Label>
                                <Input id="phone" v-model="form.phone" placeholder="3001234567" />
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>
                            <div class="flex justify-end">
                                <Button type="button" variant="outline" class="mr-2" @click="requestVerificationCode" :disabled="form.processing || !form.phone || !form.country_code">
                                    Solicitar Código
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- WhatsApp Actual -->
            <Card>
                <CardHeader>
                    <CardTitle>WhatsApp Actual</CardTitle>
                    <CardDescription>
                        Tu número de WhatsApp actual registrado en el sistema.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid w-full items-center gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label>Número Actual</Label>
                            <div class="text-lg font-medium">
                                {{ props.whatsapp.country_code }} {{ props.whatsapp.number || 'No registrado' }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Historial de Números de WhatsApp -->
            <Card v-if="hasHistory">
                <CardHeader>
                    <CardTitle>Historial de Números</CardTitle>
                    <CardDescription>
                        Historial de tus números de WhatsApp anteriores.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid w-full items-center gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <div v-for="(history, index) in props.whatsappHistory" :key="index" class="border-b border-gray-200 py-3 last:border-b-0">
                                <div class="flex justify-between">
                                    <div>
                                        <span class="font-medium">{{ history.country_code }} {{ history.phone }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ formatDate(history.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
            <!-- Mensaje si no hay historial -->
            <Card v-else>
                <CardHeader>
                    <CardTitle>Historial de Números</CardTitle>
                    <CardDescription>
                        No hay registros de cambios en tu número de WhatsApp.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-center py-4">
                        <p class="text-gray-500">No se encontraron registros en el historial.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </SettingsLayout>
    
    <!-- Modal de verificación de WhatsApp -->
    <WhatsappVerificationModal
        :country-code="form.country_code"
        :phone="form.phone"
        :show="showVerificationModal"
        @close="closeModal"
        @verified="onVerified"
    />
    
    <!-- Mensajes de éxito y error -->
    <TransitionRoot
        as="template"
        :show="successMessage !== null"
        enter="transform ease-out duration-300 transition"
        enter-from="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to="translate-y-0 opacity-100 sm:translate-x-0"
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
    >
        <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 fixed top-4 right-4 z-50">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <CheckCircle class="h-6 w-6 text-green-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">{{ successMessage }}</p>
                    </div>
                    <div class="ml-4 flex flex-shrink-0">
                        <button
                            type="button"
                            class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            @click="successMessage = null"
                        >
                            <span class="sr-only">Close</span>
                            <X class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TransitionRoot>

    <TransitionRoot
        as="template"
        :show="errorMessage !== null"
        enter="transform ease-out duration-300 transition"
        enter-from="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to="translate-y-0 opacity-100 sm:translate-x-0"
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
    >
        <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 fixed top-4 right-4 z-50">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <AlertCircle class="h-6 w-6 text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">{{ errorMessage }}</p>
                    </div>
                    <div class="ml-4 flex flex-shrink-0">
                        <button
                            type="button"
                            class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            @click="errorMessage = null"
                        >
                            <span class="sr-only">Close</span>
                            <X class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TransitionRoot>

</AppLayout>
</template>
