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
            <div>
                <h1 class="text-h5 font-weight-medium">Add expense</h1>
                <div class="text-body-2 text-medium-emphasis">{{ group.name }}</div>
            </div>
        </template>

        <div style="max-width: 720px">
            <ExpenseForm
                :group="group"
                :members="members"
                :submit-url="route('groups.expenses.store', group.id)"
                method="post"
            />
        </div>
    </AuthenticatedLayout>
</template>
