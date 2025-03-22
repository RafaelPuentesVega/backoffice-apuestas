<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    email: string;
    show: boolean;
}>();

const emit = defineEmits(['close', 'confirmed']);

const form = useForm({
    email: '',
    code: '',
});
// Observar cambios en props.email y actualizar el formulario
watch(() => props.email, (newEmail) => {
    form.email = newEmail;
}, { immediate: true });


const submit = () => {
    form.post(route('verify.code'), {
        onSuccess: () => {
            // Pass the verified code back to the parent component
            emit('confirmed', form.code);
        },
        preserveScroll: true

    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h2 class="text-lg font-semibold mb-4">Verificación de Código</h2>
            <p class="text-sm text-gray-600 mb-4">Ingresa el código que enviamos a tu correo {{ email }}.</p>
            
            <form @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="code">Código de Verificación</Label>
                    <Input id="code" type="text" required v-model="form.code" placeholder="123456" />
                    <InputError :message="form.errors.code" />
                </div>
                
                <div class="flex justify-between mt-4">
                    <Button type="button" class="bg-gray-300 text-black" @click="emit('close')">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Confirmar</Button>
                </div>
            </form>
        </div>
    </div>
</template>