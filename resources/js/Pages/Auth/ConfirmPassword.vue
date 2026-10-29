<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <h1 class="text-h6 mb-2">Confirm your password</h1>
        <p class="text-body-2 text-medium-emphasis mb-4">
            This is a secure area of the app. Please confirm your password before continuing.
        </p>

        <v-form @submit.prevent="submit">
            <v-text-field
                v-model="form.password"
                type="password"
                label="Password"
                prepend-inner-icon="mdi-lock-outline"
                autocomplete="current-password"
                :error-messages="form.errors.password"
                autofocus
            />
            <v-btn type="submit" color="primary" block size="large" :loading="form.processing">Confirm</v-btn>
        </v-form>
    </GuestLayout>
</template>
