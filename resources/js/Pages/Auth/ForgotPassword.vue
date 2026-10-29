<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <h1 class="text-h6 mb-2">Forgot your password?</h1>
        <p class="text-body-2 text-medium-emphasis mb-4">
            Enter your email and we'll send you a link to choose a new one.
        </p>

        <v-alert v-if="status" type="success" variant="tonal" density="compact" class="mb-4" :text="status" />

        <v-form @submit.prevent="submit">
            <v-text-field
                v-model="form.email"
                type="email"
                label="Email"
                prepend-inner-icon="mdi-email-outline"
                autocomplete="username"
                :error-messages="form.errors.email"
                autofocus
            />
            <v-btn type="submit" color="primary" block size="large" :loading="form.processing">
                Email password reset link
            </v-btn>
        </v-form>

        <div class="text-center mt-4 text-body-2">
            <Link :href="route('login')" class="text-primary">Back to log in</Link>
        </div>
    </GuestLayout>
</template>
