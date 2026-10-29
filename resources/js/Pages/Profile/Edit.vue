<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-h5 font-weight-medium">Profile</h1>
        </template>

        <div style="max-width: 720px">
            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="mb-6" />
            <UpdatePasswordForm class="mb-6" />
            <DeleteUserForm />
        </div>
    </AuthenticatedLayout>
</template>
