<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <v-card>
        <v-card-item>
            <v-card-title>Update password</v-card-title>
            <v-card-subtitle>Use a long, random password to stay secure.</v-card-subtitle>
        </v-card-item>
        <v-form @submit.prevent="updatePassword">
            <v-card-text>
                <v-text-field
                    v-model="form.current_password"
                    type="password"
                    label="Current password"
                    autocomplete="current-password"
                    :error-messages="form.errors.current_password"
                />
                <v-text-field
                    v-model="form.password"
                    type="password"
                    label="New password"
                    autocomplete="new-password"
                    :error-messages="form.errors.password"
                />
                <v-text-field
                    v-model="form.password_confirmation"
                    type="password"
                    label="Confirm password"
                    autocomplete="new-password"
                    :error-messages="form.errors.password_confirmation"
                />
            </v-card-text>
            <v-card-actions class="px-4 pb-4">
                <v-btn type="submit" color="primary" variant="flat" :loading="form.processing">Save</v-btn>
                <span v-if="form.recentlySuccessful" class="text-body-2 text-success ms-2">Saved.</span>
            </v-card-actions>
        </v-form>
    </v-card>
</template>
