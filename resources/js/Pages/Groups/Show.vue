<script setup>
import BalanceList from '@/Components/BalanceList.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    group: Object,
    members: Array,
    expenses: Array,
    balances: Array,
    suggestions: Array,
    settlements: Array,
});

const page = usePage();
const errors = computed(() => page.props.errors);

// Local date, not UTC, so the default isn't yesterday in the early morning.
const today = () => {
    const now = new Date();
    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
};

// One confirm dialog shared by every delete/remove button.
const confirmation = ref({ open: false, title: '', text: '', confirmText: 'Delete', action: null });

const askToConfirm = (title, text, confirmText, action) => {
    confirmation.value = { open: true, title, text, confirmText, action };
};

const deleteExpense = (expense) =>
    askToConfirm('Delete expense?', `"${expense.description}" will be removed for everyone.`, 'Delete', () =>
        router.delete(route('groups.expenses.destroy', [props.group.id, expense.id]), { preserveScroll: true }),
    );

const deleteSettlement = (settlement) =>
    askToConfirm(
        'Delete payment?',
        `The ${settlement.amount_display} payment from ${settlement.from_name} will be removed.`,
        'Delete',
        () =>
            router.delete(route('groups.settlements.destroy', [props.group.id, settlement.id]), {
                preserveScroll: true,
            }),
    );

const removeMember = (member) =>
    askToConfirm('Remove member?', `${member.name} will be removed from ${props.group.name}.`, 'Remove', () =>
        router.delete(route('groups.members.destroy', [props.group.id, member.id]), { preserveScroll: true }),
    );

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
            <div>
                <h1 class="text-h5 font-weight-medium">{{ group.name }}</h1>
                <div class="text-body-2 text-medium-emphasis">
                    {{ members.length }} {{ members.length === 1 ? 'member' : 'members' }} · {{ group.currency }}
                </div>
            </div>
            <v-spacer />
            <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="router.visit(route('groups.expenses.create', group.id))"
            >
                Add expense
            </v-btn>
        </template>

        <v-row>
            <v-col cols="12" md="8">
                <v-card class="mb-6">
                    <v-card-title class="d-flex align-center">
                        <v-icon icon="mdi-receipt-text-outline" class="me-2" />
                        Expenses
                    </v-card-title>
                    <v-card-text v-if="expenses.length === 0" class="text-medium-emphasis">
                        No expenses yet. Add the first one to start splitting.
                    </v-card-text>
                    <v-list v-else lines="two">
                        <template v-for="(expense, index) in expenses" :key="expense.id">
                            <v-divider v-if="index > 0" />
                            <v-list-item>
                                <v-list-item-title class="font-weight-medium">
                                    {{ expense.description }}
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ expense.payer_name }} paid · {{ expense.spent_on }}
                                </v-list-item-subtitle>
                                <template #append>
                                    <span class="font-weight-medium me-2">{{ expense.amount_display }}</span>
                                    <v-btn
                                        icon="mdi-pencil-outline"
                                        variant="text"
                                        size="small"
                                        aria-label="Edit expense"
                                        @click="router.visit(route('groups.expenses.edit', [group.id, expense.id]))"
                                    />
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        variant="text"
                                        size="small"
                                        color="error"
                                        aria-label="Delete expense"
                                        @click="deleteExpense(expense)"
                                    />
                                </template>
                            </v-list-item>
                        </template>
                    </v-list>
                </v-card>

                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon icon="mdi-cash-fast" class="me-2" />
                        Payments
                    </v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="recordPayment">
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <v-select
                                        v-model="paymentForm.from_user_id"
                                        :items="members"
                                        item-title="name"
                                        item-value="id"
                                        label="Who paid"
                                        :error-messages="errors.from_user_id"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-select
                                        v-model="paymentForm.to_user_id"
                                        :items="members"
                                        item-title="name"
                                        item-value="id"
                                        label="Paid to"
                                        :error-messages="errors.to_user_id"
                                    />
                                </v-col>
                                <v-col cols="12" sm="4">
                                    <v-text-field
                                        v-model="paymentForm.amount"
                                        label="Amount"
                                        :prefix="group.currency"
                                        inputmode="decimal"
                                        :error-messages="errors.amount"
                                    />
                                </v-col>
                                <v-col cols="12" sm="4">
                                    <v-text-field
                                        v-model="paymentForm.settled_on"
                                        type="date"
                                        label="Date"
                                        :error-messages="errors.settled_on"
                                    />
                                </v-col>
                                <v-col cols="12" sm="4">
                                    <v-btn
                                        type="submit"
                                        color="primary"
                                        variant="tonal"
                                        block
                                        height="48"
                                        :loading="paymentForm.processing"
                                    >
                                        Record
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>

                    <v-card-text v-if="settlements.length === 0" class="pt-0 text-medium-emphasis">
                        No payments recorded yet.
                    </v-card-text>
                    <v-list v-else>
                        <template v-for="(settlement, index) in settlements" :key="settlement.id">
                            <v-divider v-if="index > 0" />
                            <v-list-item prepend-icon="mdi-arrow-right-circle-outline">
                                <v-list-item-title class="text-wrap">
                                    <strong>{{ settlement.from_name }}</strong> paid
                                    <strong>{{ settlement.to_name }}</strong>
                                </v-list-item-title>
                                <v-list-item-subtitle>{{ settlement.settled_on }}</v-list-item-subtitle>
                                <template #append>
                                    <span class="font-weight-medium me-2">{{ settlement.amount_display }}</span>
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        variant="text"
                                        size="small"
                                        color="error"
                                        aria-label="Delete payment"
                                        @click="deleteSettlement(settlement)"
                                    />
                                </template>
                            </v-list-item>
                        </template>
                    </v-list>
                </v-card>
            </v-col>

            <v-col cols="12" md="4">
                <v-card class="mb-6">
                    <v-card-title class="d-flex align-center">
                        <v-icon icon="mdi-scale-balance" class="me-2" />
                        Balances
                    </v-card-title>
                    <v-card-text>
                        <BalanceList :balances="balances" />
                    </v-card-text>
                </v-card>

                <v-card class="mb-6">
                    <v-card-title class="d-flex align-center">
                        <v-icon icon="mdi-swap-horizontal" class="me-2" />
                        Suggested payments
                    </v-card-title>
                    <v-card-text v-if="suggestions.length === 0">
                        <v-alert type="success" variant="tonal" density="compact" text="Everyone is settled up." />
                    </v-card-text>
                    <v-list v-else>
                        <v-list-item
                            v-for="suggestion in suggestions"
                            :key="`${suggestion.from_id}-${suggestion.to_id}`"
                        >
                            <v-list-item-title class="text-wrap">
                                <strong>{{ suggestion.from_name }}</strong> pays
                                <strong>{{ suggestion.to_name }}</strong>
                            </v-list-item-title>
                            <v-list-item-subtitle>{{ suggestion.display }}</v-list-item-subtitle>
                            <template #append>
                                <v-btn size="small" color="primary" variant="tonal" @click="markAsPaid(suggestion)">
                                    Mark as paid
                                </v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card>

                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon icon="mdi-account-multiple-outline" class="me-2" />
                        Members
                    </v-card-title>
                    <v-list density="compact">
                        <v-list-item
                            v-for="member in members"
                            :key="member.id"
                            :title="member.name"
                            :subtitle="member.email"
                        >
                            <template #prepend>
                                <v-avatar size="32" color="secondary" variant="tonal" class="me-3">
                                    {{ member.name.charAt(0).toUpperCase() }}
                                </v-avatar>
                            </template>
                            <template #append>
                                <v-btn
                                    icon="mdi-account-remove-outline"
                                    variant="text"
                                    size="small"
                                    aria-label="Remove member"
                                    @click="removeMember(member)"
                                />
                            </template>
                        </v-list-item>
                    </v-list>
                    <v-card-text>
                        <v-alert
                            v-if="errors.member"
                            type="error"
                            variant="tonal"
                            density="compact"
                            class="mb-4"
                            :text="errors.member"
                        />
                        <v-form @submit.prevent="addMember">
                            <v-text-field
                                v-model="memberForm.email"
                                type="email"
                                label="Add by email"
                                placeholder="friend@example.com"
                                :error-messages="memberForm.errors.email"
                            >
                                <template #append-inner>
                                    <v-btn
                                        type="submit"
                                        icon="mdi-account-plus-outline"
                                        variant="text"
                                        size="small"
                                        color="primary"
                                        aria-label="Add member"
                                        :loading="memberForm.processing"
                                    />
                                </template>
                            </v-text-field>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <ConfirmDialog
            v-model="confirmation.open"
            :title="confirmation.title"
            :text="confirmation.text"
            :confirm-text="confirmation.confirmText"
            @confirm="confirmation.action?.()"
        />
    </AuthenticatedLayout>
</template>
