<script setup>
import BalanceList from '@/Components/BalanceList.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
    expenses: Array,
    balances: Array,
    suggestions: Array,
});

const deleteExpense = (expense) => {
    if (confirm(`Delete "${expense.description}"?`)) {
        router.delete(route('groups.expenses.destroy', [props.group.id, expense.id]), {
            preserveScroll: true,
        });
    }
};

const memberForm = useForm({ email: '' });

const addMember = () => {
    memberForm.post(route('groups.members.store', props.group.id), {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
};
</script>

<template>
    <Head :title="group.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ group.name }}
                <span class="ms-2 text-sm font-normal text-gray-500">{{ group.currency }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto grid max-w-7xl gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="space-y-6 lg:col-span-2">
                    <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Expenses</h3>
                            <Link
                                :href="route('groups.expenses.create', group.id)"
                                class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                            >
                                Add expense
                            </Link>
                        </div>
                        <p v-if="expenses.length === 0" class="mt-4 text-sm text-gray-600">
                            No expenses yet.
                        </p>
                        <ul v-else class="mt-4 divide-y divide-gray-100">
                            <li v-for="expense in expenses" :key="expense.id" class="flex items-center justify-between gap-4 py-3">
                                <div>
                                    <div class="font-medium text-gray-900">{{ expense.description }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ expense.payer_name }} paid · {{ expense.spent_on }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="font-medium text-gray-900">{{ expense.amount_display }}</span>
                                    <Link
                                        :href="route('groups.expenses.edit', [group.id, expense.id])"
                                        class="text-sm text-indigo-600 hover:text-indigo-800"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="text-sm text-red-600 hover:text-red-800"
                                        @click="deleteExpense(expense)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>

                <div class="space-y-6">
                <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Balances</h3>
                    <BalanceList class="mt-2" :balances="balances" />
                </section>

                <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Suggested payments</h3>
                    <p v-if="suggestions.length === 0" class="mt-2 text-sm text-gray-600">
                        Everyone is settled up.
                    </p>
                    <ul v-else class="mt-2 divide-y divide-gray-100">
                        <li
                            v-for="suggestion in suggestions"
                            :key="`${suggestion.from_id}-${suggestion.to_id}`"
                            class="flex items-center justify-between gap-2 py-2 text-sm"
                        >
                            <span>
                                <strong>{{ suggestion.from_name }}</strong> pays
                                <strong>{{ suggestion.to_name }}</strong>
                                {{ suggestion.display }}
                            </span>
                        </li>
                    </ul>
                </section>

                <section class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Members</h3>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="member in members" :key="member.id" class="py-2">
                            <div class="font-medium text-gray-900">{{ member.name }}</div>
                            <div class="text-sm text-gray-500">{{ member.email }}</div>
                        </li>
                    </ul>

                    <form @submit.prevent="addMember" class="space-y-2">
                        <TextInput
                            v-model="memberForm.email"
                            type="email"
                            class="block w-full"
                            placeholder="friend@example.com"
                            required
                        />
                        <InputError :message="memberForm.errors.email" />
                        <PrimaryButton :disabled="memberForm.processing">Add member</PrimaryButton>
                    </form>
                </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
