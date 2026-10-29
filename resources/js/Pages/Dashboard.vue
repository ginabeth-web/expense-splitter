<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    groups: Array,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
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
                    <div v-if="groups.length === 0" class="p-6 text-gray-600">
                        You're not in any groups yet.
                        <Link :href="route('groups.create')" class="text-indigo-600 hover:text-indigo-800">
                            Create your first group
                        </Link>
                        to start splitting expenses.
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="group in groups" :key="group.id">
                            <Link
                                :href="route('groups.show', group.id)"
                                class="flex items-center justify-between p-6 hover:bg-gray-50"
                            >
                                <span class="font-medium text-gray-900">{{ group.name }}</span>
                                <span v-if="group.my_balance_cents > 0" class="text-sm text-green-700">
                                    you are owed {{ group.my_balance_display }}
                                </span>
                                <span v-else-if="group.my_balance_cents < 0" class="text-sm text-red-700">
                                    you owe {{ group.my_balance_display }}
                                </span>
                                <span v-else class="text-sm text-gray-500">settled up</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
