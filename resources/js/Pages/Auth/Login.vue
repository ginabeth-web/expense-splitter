<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <h1 class="text-h6 mb-4">Log in</h1>

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
            <v-text-field
                v-model="form.password"
                type="password"
                label="Password"
                prepend-inner-icon="mdi-lock-outline"
                autocomplete="current-password"
                :error-messages="form.errors.password"
            />
            <v-checkbox v-model="form.remember" label="Remember me" color="primary" density="compact" hide-details />

            <v-btn type="submit" color="primary" block size="large" class="mt-4" :loading="form.processing">
                Log in
            </v-btn>
        </v-form>

        <div class="d-flex justify-space-between mt-4 text-body-2">
            <Link v-if="canResetPassword" :href="route('password.request')" class="text-primary">
                Forgot your password?
            </Link>
            <Link :href="route('register')" class="text-primary">Create an account</Link>
        </div>
    </GuestLayout>
</template>
