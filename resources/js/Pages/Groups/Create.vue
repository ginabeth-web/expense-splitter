<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    currency: 'PHP',
});
</script>

<template>
    <Head title="New group" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-h5 font-weight-medium">New group</h1>
        </template>

        <v-card max-width="560" class="pa-2">
            <v-form @submit.prevent="form.post(route('groups.store'))">
                <v-card-text>
                    <v-text-field
                        v-model="form.name"
                        label="Name"
                        placeholder="Baguio Trip"
                        :error-messages="form.errors.name"
                        autofocus
                    />
                    <v-text-field
                        :model-value="form.currency"
                        label="Currency code"
                        hint="Three letters, like PHP or USD"
                        persistent-hint
                        maxlength="3"
                        style="max-width: 200px"
                        :error-messages="form.errors.currency"
                        @update:model-value="(value) => (form.currency = value.toUpperCase())"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-btn type="submit" color="primary" variant="flat" :loading="form.processing">
                        Create group
                    </v-btn>
                    <v-btn variant="text" @click="router.visit(route('groups.index'))">Cancel</v-btn>
                </v-card-actions>
            </v-form>
        </v-card>
    </AuthenticatedLayout>
</template>
