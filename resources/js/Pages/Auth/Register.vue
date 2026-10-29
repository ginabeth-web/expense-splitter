<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h1 class="text-h6 mb-4">Create an account</h1>

        <v-form @submit.prevent="submit">
            <v-text-field
                v-model="form.name"
                label="Name"
                prepend-inner-icon="mdi-account-outline"
                autocomplete="name"
                :error-messages="form.errors.name"
                autofocus
            />
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
                label="Password"
                prepend-inner-icon="mdi-lock-outline"
                autocomplete="new-password"
                :error-messages="form.errors.password"
            />
            <v-text-field
                v-model="form.password_confirmation"
                type="password"
                label="Confirm password"
                prepend-inner-icon="mdi-lock-check-outline"
                autocomplete="new-password"
                :error-messages="form.errors.password_confirmation"
            />

            <v-btn type="submit" color="primary" block size="large" class="mt-2" :loading="form.processing">
                Register
            </v-btn>
        </v-form>

        <div class="text-center mt-4 text-body-2">
            <Link :href="route('login')" class="text-primary">Already registered? Log in</Link>
        </div>
    </GuestLayout>
</template>
