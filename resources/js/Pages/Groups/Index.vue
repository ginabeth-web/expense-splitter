<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    groups: Array,
});
</script>

<template>
    <Head title="Groups" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Groups
                </h2>
                <Link
                    :href="route('groups.create')"
                    class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                >
                    New group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <p v-if="groups.length === 0" class="p-6 text-gray-600">
                        You're not in any groups yet.
                    </p>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="group in groups" :key="group.id">
                            <Link
                                :href="route('groups.show', group.id)"
                                class="flex justify-between p-6 hover:bg-gray-50"
                            >
                                <span class="font-medium text-gray-900">{{ group.name }}</span>
                                <span class="text-sm text-gray-500">{{ group.currency }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
