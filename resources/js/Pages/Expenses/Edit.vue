<script setup>
import ExpenseForm from '@/Components/ExpenseForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    group: Object,
    members: Array,
    expense: Object,
});
</script>

<template>
    <Head title="Edit expense" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit expense in {{ group.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <ExpenseForm
                    :group="group"
                    :members="members"
                    :expense="expense"
                    :submit-url="route('groups.expenses.update', [group.id, expense.id])"
                    method="put"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
