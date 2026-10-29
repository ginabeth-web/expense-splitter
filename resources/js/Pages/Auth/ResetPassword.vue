<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <h1 class="text-h6 mb-4">Choose a new password</h1>

        <v-form @submit.prevent="submit">
            <v-text-field
                v-model="form.email"
                type="email"
                label="Email"
                prepend-inner-icon="mdi-email-outline"
                autocomplete="username"
                :error-messages="form.errors.email"
            />
            <v-text-field
                v-model="form.password"
                type="password"
                label="New password"
                prepend-inner-icon="mdi-lock-outline"
                autocomplete="new-password"
                :error-messages="form.errors.password"
                autofocus
            />
            <v-text-field
                v-model="form.password_confirmation"
                type="password"
                label="Confirm password"
                prepend-inner-icon="mdi-lock-check-outline"
                autocomplete="new-password"
                :error-messages="form.errors.password_confirmation"
            />
            <v-btn type="submit" color="primary" block size="large" :loading="form.processing">
                Reset password
            </v-btn>
        </v-form>
    </GuestLayout>
</template>
