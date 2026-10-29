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
            <div>
                <h1 class="text-h5 font-weight-medium">Edit expense</h1>
                <div class="text-body-2 text-medium-emphasis">{{ group.name }}</div>
            </div>
        </template>

        <div style="max-width: 720px">
            <ExpenseForm
                :group="group"
                :members="members"
                :expense="expense"
                :submit-url="route('groups.expenses.update', [group.id, expense.id])"
                method="put"
            />
        </div>
    </AuthenticatedLayout>
</template>
