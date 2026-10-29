<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    groups: Array,
});
</script>

<template>
    <Head title="Groups" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-h5 font-weight-medium">Groups</h1>
            <v-spacer />
            <v-btn color="primary" prepend-icon="mdi-plus" @click="router.visit(route('groups.create'))">
                New group
            </v-btn>
        </template>

        <v-card>
            <v-card-text v-if="groups.length === 0" class="text-medium-emphasis">
                You're not in any groups yet.
            </v-card-text>
            <v-list v-else lines="one">
                <v-list-item
                    v-for="group in groups"
                    :key="group.id"
                    :title="group.name"
                    prepend-icon="mdi-account-group-outline"
                    append-icon="mdi-chevron-right"
                    @click="router.visit(route('groups.show', group.id))"
                >
                    <template #subtitle>{{ group.currency }}</template>
                </v-list-item>
            </v-list>
        </v-card>
    </AuthenticatedLayout>
</template>
