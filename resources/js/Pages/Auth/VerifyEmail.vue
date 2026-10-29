<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <h1 class="text-h6 mb-2">Verify your email</h1>
        <p class="text-body-2 text-medium-emphasis mb-4">
            Thanks for signing up! Please click the link we just emailed you. If it didn't arrive, we'll gladly
            send another.
        </p>

        <v-alert
            v-if="verificationLinkSent"
            type="success"
            variant="tonal"
            density="compact"
            class="mb-4"
            text="A new verification link has been sent to your email address."
        />

        <v-form @submit.prevent="submit">
            <v-btn type="submit" color="primary" block size="large" :loading="form.processing">
                Resend verification email
            </v-btn>
        </v-form>

        <v-btn variant="text" block class="mt-2" @click="router.post(route('logout'))">Log out</v-btn>
    </GuestLayout>
</template>
