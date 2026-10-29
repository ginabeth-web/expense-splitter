<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    groups: Array,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-h5 font-weight-medium">Your groups</h1>
            <v-spacer />
            <v-btn color="primary" prepend-icon="mdi-plus" @click="router.visit(route('groups.create'))">
                New group
            </v-btn>
        </template>

        <v-card v-if="groups.length === 0" class="pa-8 text-center">
            <v-icon icon="mdi-account-group-outline" size="64" color="primary" class="mb-4" />
            <div class="text-h6 mb-2">You're not in any groups yet</div>
            <p class="text-medium-emphasis mb-6">
                Make a group for a trip, your household or a night out, then start adding expenses.
            </p>
            <v-btn color="primary" prepend-icon="mdi-plus" @click="router.visit(route('groups.create'))">
                Create your first group
            </v-btn>
        </v-card>

        <v-row v-else>
            <v-col v-for="group in groups" :key="group.id" cols="12" sm="6" lg="4">
                <v-card hover @click="router.visit(route('groups.show', group.id))">
                    <v-card-item>
                        <template #prepend>
                            <v-avatar color="primary" variant="tonal">
                                <v-icon icon="mdi-account-group" />
                            </v-avatar>
                        </template>
                        <v-card-title>{{ group.name }}</v-card-title>
                        <v-card-subtitle>{{ group.currency }}</v-card-subtitle>
                    </v-card-item>
                    <v-card-text>
                        <v-chip v-if="group.my_balance_cents > 0" color="success" variant="tonal">
                            You are owed {{ group.my_balance_display }}
                        </v-chip>
                        <v-chip v-else-if="group.my_balance_cents < 0" color="error" variant="tonal">
                            You owe {{ group.my_balance_display }}
                        </v-chip>
                        <v-chip v-else variant="tonal">Settled up</v-chip>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </AuthenticatedLayout>
</template>
