<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <v-card>
        <v-card-item>
            <v-card-title>Delete account</v-card-title>
            <v-card-subtitle class="text-wrap">
                Once your account is deleted, all of its data is gone for good. You can only delete it after leaving
                every group.
            </v-card-subtitle>
        </v-card-item>
        <v-card-actions class="px-4 pb-4">
            <v-btn color="error" variant="flat" @click="confirmingUserDeletion = true">Delete account</v-btn>
        </v-card-actions>

        <v-dialog v-model="confirmingUserDeletion" max-width="480" @after-leave="closeModal">
            <v-card title="Are you sure you want to delete your account?">
                <v-form @submit.prevent="deleteUser">
                    <v-card-text>
                        <p class="text-body-2 mb-4">
                            This can't be undone. Enter your password to confirm.
                        </p>
                        <v-text-field
                            v-model="form.password"
                            type="password"
                            label="Password"
                            autocomplete="current-password"
                            :error-messages="form.errors.password"
                            autofocus
                        />
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="closeModal">Cancel</v-btn>
                        <v-btn type="submit" color="error" variant="flat" :loading="form.processing">
                            Delete account
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>
    </v-card>
</template>
