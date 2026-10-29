<script setup>
import ExpenseForm from '@/Components/ExpenseForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    group: Object,
    members: Array,
});
</script>

<template>
    <Head title="Add expense" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Add expense to {{ group.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <ExpenseForm
                    :group="group"
                    :members="members"
                    :submit-url="route('groups.expenses.store', group.id)"
                    method="post"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
