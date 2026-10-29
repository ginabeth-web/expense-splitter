<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    group: Object,
    members: Array,
    expense: { type: Object, default: null },
    submitUrl: String,
    method: { type: String, default: 'post' },
});

// Local date, not UTC, so the default isn't yesterday in the early morning.
const now = new Date();
const today = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const blankMap = () => Object.fromEntries(props.members.map((m) => [m.id, '']));

const form = useForm({
    description: props.expense?.description ?? '',
    amount: props.expense?.amount ?? '',
    spent_on: props.expense?.spent_on ?? today,
    paid_by: props.expense?.paid_by ?? usePage().props.auth.user.id,
    split_type: props.expense?.split_type ?? 'equal',
    participants: props.expense?.participants ?? props.members.map((m) => m.id),
    shares: { ...blankMap(), ...(props.expense?.shares ?? {}) },
    percents: { ...blankMap(), ...(props.expense?.percents ?? {}) },
});

// Display-only hint while typing; the server does the real check.
const toNumber = (value) => parseFloat(String(value).replace(/,/g, '')) || 0;
const sum = (values) => Object.values(values).reduce((total, v) => total + toNumber(v), 0);

const remaining = computed(() => {
    if (form.split_type === 'exact') {
        const left = toNumber(form.amount) - sum(form.shares);
        return { left, text: `${props.group.currency} ${left.toFixed(2)} left to assign` };
    }
    if (form.split_type === 'percent') {
        const left = 100 - sum(form.percents);
        return { left, text: `${left.toFixed(2)}% left to assign` };
    }
    return null;
});

const participantErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([key]) => key === 'participants' || key.startsWith('participants.'))
        .map(([, message]) => message),
);

const submit = () => {
    form.submit(props.method, props.submitUrl);
};
</script>

<template>
    <v-card class="pa-2">
        <v-form @submit.prevent="submit">
            <v-card-text>
                <v-text-field
                    v-model="form.description"
                    label="What was it for?"
                    placeholder="Dinner"
                    prepend-inner-icon="mdi-text"
                    :error-messages="form.errors.description"
                    autofocus
                />

                <v-row dense>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.amount"
                            label="Amount"
                            :prefix="group.currency"
                            inputmode="decimal"
                            placeholder="0.00"
                            :error-messages="form.errors.amount"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.spent_on"
                            type="date"
                            label="Date"
                            :error-messages="form.errors.spent_on"
                        />
                    </v-col>
                </v-row>

                <v-select
                    v-model="form.paid_by"
                    :items="members"
                    item-title="name"
                    item-value="id"
                    label="Paid by"
                    prepend-inner-icon="mdi-account-cash-outline"
                    :error-messages="form.errors.paid_by"
                />

                <div class="text-subtitle-2 mb-2">Split</div>
                <v-btn-toggle
                    v-model="form.split_type"
                    color="primary"
                    variant="outlined"
                    divided
                    mandatory
                    density="comfortable"
                    class="mb-4 flex-wrap"
                >
                    <v-btn value="equal" prepend-icon="mdi-equal">Equally</v-btn>
                    <v-btn value="exact" prepend-icon="mdi-cash">Exact amounts</v-btn>
                    <v-btn value="percent" prepend-icon="mdi-percent-outline">Percentage</v-btn>
                </v-btn-toggle>

                <v-sheet border rounded="lg" class="pa-4">
                    <template v-if="form.split_type === 'equal'">
                        <div class="text-body-2 text-medium-emphasis mb-2">Who shares this expense?</div>
                        <v-checkbox
                            v-for="member in members"
                            :key="member.id"
                            v-model="form.participants"
                            :value="member.id"
                            :label="member.name"
                            color="primary"
                            density="compact"
                            hide-details
                        />
                        <v-alert
                            v-for="message in participantErrors"
                            :key="message"
                            type="error"
                            variant="tonal"
                            density="compact"
                            class="mt-3"
                            :text="message"
                        />
                    </template>

                    <template v-else>
                        <div class="text-body-2 text-medium-emphasis mb-3">
                            Leave someone blank if they aren't part of it.
                        </div>
                        <v-row v-for="member in members" :key="member.id" dense align="center">
                            <v-col cols="6" sm="5" class="text-body-1">{{ member.name }}</v-col>
                            <v-col cols="6" sm="4">
                                <v-text-field
                                    v-if="form.split_type === 'exact'"
                                    v-model="form.shares[member.id]"
                                    :prefix="group.currency"
                                    inputmode="decimal"
                                    placeholder="0.00"
                                    density="compact"
                                    hide-details
                                />
                                <v-text-field
                                    v-else
                                    v-model="form.percents[member.id]"
                                    suffix="%"
                                    inputmode="decimal"
                                    placeholder="0"
                                    density="compact"
                                    hide-details
                                />
                            </v-col>
                        </v-row>
                        <v-alert
                            :type="Math.abs(remaining.left) < 0.005 ? 'success' : 'info'"
                            variant="tonal"
                            density="compact"
                            class="mt-4"
                            :text="remaining.text"
                        />
                        <v-alert
                            v-if="form.split_type === 'exact' ? form.errors.shares : form.errors.percents"
                            type="error"
                            variant="tonal"
                            density="compact"
                            class="mt-3"
                            :text="form.split_type === 'exact' ? form.errors.shares : form.errors.percents"
                        />
                    </template>
                </v-sheet>
            </v-card-text>

            <v-card-actions>
                <v-btn type="submit" color="primary" variant="flat" :loading="form.processing">
                    Save expense
                </v-btn>
                <v-btn variant="text" @click="router.visit(route('groups.show', group.id))">Cancel</v-btn>
            </v-card-actions>
        </v-form>
    </v-card>
</template>
