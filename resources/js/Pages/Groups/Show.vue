<script setup>
import BalanceList from '@/Components/BalanceList.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
    expenses: Array,
    balances: Array,
    suggestions: Array,
    settlements: Array,
});

const today = () => new Date().toISOString().slice(0, 10);

const deleteExpense = (expense) => {
    if (confirm(`Delete "${expense.description}"?`)) {
        router.delete(route('groups.expenses.destroy', [props.group.id, expense.id]), {
            preserveScroll: true,
        });
    }
};

const markAsPaid = (suggestion) => {
    router.post(
        route('groups.settlements.store', props.group.id),
        {
            from_user_id: suggestion.from_id,
            to_user_id: suggestion.to_id,
            amount: suggestion.amount,
            settled_on: today(),
        },
        { preserveScroll: true },
    );
};

const paymentForm = useForm({
    from_user_id: props.members[0]?.id ?? null,
    to_user_id: props.members[1]?.id ?? null,
    amount: '',
    settled_on: today(),
});

const recordPayment = () => {
    paymentForm.post(route('groups.settlements.store', props.group.id), {
        preserveScroll: true,
        onSuccess: () => paymentForm.reset('amount'),
    });
};

const deleteSettlement = (settlement) => {
    if (confirm(`Delete the ${settlement.amount_display} payment from ${settlement.from_name}?`)) {
        router.delete(route('groups.settlements.destroy', [props.group.id, settlement.id]), {
            preserveScroll: true,
        });
    }
};

const removeMember = (member) => {
    if (confirm(`Remove ${member.name} from ${props.group.name}?`)) {
        router.delete(route('groups.members.destroy', [props.group.id, member.id]), {
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
                        <p v-if="expenses.length === 0" class="mt-4 text-sm text-gray-600">No expenses yet.</p>
                        <ul v-else class="mt-4 divide-y divide-gray-100">
                            <li
                                v-for="expense in expenses"
                                :key="expense.id"
                                class="flex items-center justify-between gap-4 py-3"
                            >
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

                    <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">Payments</h3>

                        <form @submit.prevent="recordPayment" class="mt-4 grid items-end gap-4 sm:grid-cols-5">
                            <div>
                                <InputLabel for="from_user_id" value="Who paid" />
                                <select
                                    id="from_user_id"
                                    v-model="paymentForm.from_user_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option v-for="member in members" :key="member.id" :value="member.id">
                                        {{ member.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="to_user_id" value="Paid to" />
                                <select
                                    id="to_user_id"
                                    v-model="paymentForm.to_user_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option v-for="member in members" :key="member.id" :value="member.id">
                                        {{ member.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="payment_amount" :value="`Amount (${group.currency})`" />
                                <TextInput
                                    id="payment_amount"
                                    v-model="paymentForm.amount"
                                    inputmode="decimal"
                                    class="mt-1 block w-full"
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel for="settled_on" value="Date" />
                                <TextInput
                                    id="settled_on"
                                    v-model="paymentForm.settled_on"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                            </div>
                            <PrimaryButton :disabled="paymentForm.processing" class="justify-center">
                                Record
                            </PrimaryButton>
                        </form>
                        <div class="mt-2 space-y-1">
                            <InputError :message="paymentForm.errors.from_user_id" />
                            <InputError :message="paymentForm.errors.to_user_id" />
                            <InputError :message="paymentForm.errors.amount" />
                            <InputError :message="paymentForm.errors.settled_on" />
                        </div>

                        <p v-if="settlements.length === 0" class="mt-4 text-sm text-gray-600">
                            No payments recorded yet.
                        </p>
                        <ul v-else class="mt-4 divide-y divide-gray-100">
                            <li
                                v-for="settlement in settlements"
                                :key="settlement.id"
                                class="flex items-center justify-between gap-4 py-3 text-sm"
                            >
                                <span>
                                    <strong>{{ settlement.from_name }}</strong> paid
                                    <strong>{{ settlement.to_name }}</strong>
                                    <span class="text-gray-500"> · {{ settlement.settled_on }}</span>
                                </span>
                                <span class="flex items-center gap-4">
                                    <span class="font-medium text-gray-900">{{ settlement.amount_display }}</span>
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-800"
                                        @click="deleteSettlement(settlement)"
                                    >
                                        Delete
                                    </button>
                                </span>
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
                                <SecondaryButton @click="markAsPaid(suggestion)">Mark as paid</SecondaryButton>
                            </li>
                        </ul>
                    </section>

                    <section class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">Members</h3>
                        <ul class="divide-y divide-gray-100">
                            <li
                                v-for="member in members"
                                :key="member.id"
                                class="flex items-center justify-between gap-2 py-2"
                            >
                                <div>
                                    <div class="font-medium text-gray-900">{{ member.name }}</div>
                                    <div class="text-sm text-gray-500">{{ member.email }}</div>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-800"
                                    @click="removeMember(member)"
                                >
                                    Remove
                                </button>
                            </li>
                        </ul>
                        <InputError :message="$page.props.errors.member" />

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
