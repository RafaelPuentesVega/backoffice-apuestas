<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Copy, Check } from 'lucide-vue-next';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User, type Wallet } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    wallet?: Wallet;
    code_referral?: string;
    whatsapp_number?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración de la cuenta',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    whatsapp_number: props.whatsapp_number || '',
    code_referral: props.code_referral || '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const referralUrl = computed(() => {
    return route('register', { sponsor: props.code_referral });
});

const copied = ref(false);
const copyToClipboard = () => {
    navigator.clipboard.writeText(referralUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name and email address" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name"
                            placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autocomplete="username" placeholder="Email address" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">WhatsApp Number</Label>
                        <Input id="phone" type="text" class="mt-1 block w-full" v-model="form.whatsapp_number"
                            placeholder="WhatsApp Number" />
                        <InputError class="mt-2" :message="form.errors.whatsapp_number" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link :href="route('verification.send')" method="post" as="button"
                                class="hover:!decoration-current text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out dark:decoration-neutral-500">
                            Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>


                    <!-- Wallet Information -->
                    <div class="border-t pt-6">
                        <HeadingSmall title="Wallet Information" description="Your wallet address for withdrawals" />

                        <div class="mt-4 p-4  dark:bg-gray-800 rounded-md">
                            <div v-if="props.wallet">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Wallet Address:</p>
                                <p class="text-sm mt-1 break-all">{{ props.wallet.address }}</p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-3">Wallet Type:</p>
                                <p class="text-sm mt-1">{{ props.wallet.type }}</p>
                            </div>
                            <div v-else>
                                <p class="text-sm text-gray-600 dark:text-gray-400">You haven't set up your wallet yet.
                                </p>
                                <Link :href="route('settings.wallet')"
                                    class="mt-2 inline-flex items-center text-sm font-medium text-primary hover:underline">
                                Configure your wallet
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Referral Code -->
                    <div class="border-t pt-6">
                        <HeadingSmall title="Referral Code"
                            description="Share this link with others to earn referral bonuses" />

                        <div class="mt-4">
                            <div class="flex items-center">
                                <Input readonly v-model="referralUrl" class="flex-1 pr-10" />
                                <Button variant="ghost" size="icon" class="-ml-10" @click="copyToClipboard"
                                    title="Copy to clipboard">
                                    <Check v-if="copied" class="h-4 w-4" />
                                    <Copy v-else class="h-4 w-4" />
                                </Button>
                            </div>
                            <p v-if="copied" class="text-xs text-green-600 mt-1">Copied to clipboard!</p>
                        </div>
                    </div>

                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
