<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <v-card>
        <v-card-item>
            <v-card-title>Profile information</v-card-title>
            <v-card-subtitle>Update your name and email address.</v-card-subtitle>
        </v-card-item>
        <v-form @submit.prevent="form.patch(route('profile.update'))">
            <v-card-text>
                <v-text-field
                    v-model="form.name"
                    label="Name"
                    autocomplete="name"
                    :error-messages="form.errors.name"
                />
                <v-text-field
                    v-model="form.email"
                    type="email"
                    label="Email"
                    autocomplete="username"
                    :error-messages="form.errors.email"
                />

                <div v-if="mustVerifyEmail && user.email_verified_at === null" class="text-body-2">
                    Your email address is unverified.
                    <Link :href="route('verification.send')" method="post" as="button" class="text-primary">
                        Click here to resend the verification email.
                    </Link>
                    <v-alert
                        v-if="status === 'verification-link-sent'"
                        type="success"
                        variant="tonal"
                        density="compact"
                        class="mt-2"
                        text="A new verification link has been sent to your email address."
                    />
                </div>
            </v-card-text>
            <v-card-actions class="px-4 pb-4">
                <v-btn type="submit" color="primary" variant="flat" :loading="form.processing">Save</v-btn>
                <span v-if="form.recentlySuccessful" class="text-body-2 text-success ms-2">Saved.</span>
            </v-card-actions>
        </v-form>
    </v-card>
</template>
