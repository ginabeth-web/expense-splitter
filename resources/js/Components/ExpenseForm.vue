<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    group: Object,
    members: Array,
    expense: { type: Object, default: null },
    submitUrl: String,
    method: { type: String, default: 'post' },
});

const today = new Date().toISOString().slice(0, 10);
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

const splitTypes = [
    { value: 'equal', label: 'Equally' },
    { value: 'exact', label: 'By exact amounts' },
    { value: 'percent', label: 'By percentage' },
];

// Display-only hint while typing; the server does the real check.
const sum = (values) =>
    Object.values(values).reduce((total, v) => total + (parseFloat(String(v).replace(/,/g, '')) || 0), 0);

const remaining = computed(() => {
    if (form.split_type === 'exact') {
        const left = (parseFloat(String(form.amount).replace(/,/g, '')) || 0) - sum(form.shares);
        return `${props.group.currency} ${left.toFixed(2)} left to assign`;
    }
    if (form.split_type === 'percent') {
        return `${(100 - sum(form.percents)).toFixed(2)}% left to assign`;
    }
    return null;
});

const submit = () => {
    form.submit(props.method, props.submitUrl);
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
        <div>
            <InputLabel for="description" value="What was it for?" />
            <TextInput
                id="description"
                v-model="form.description"
                class="mt-1 block w-full"
                placeholder="Dinner"
                required
                autofocus
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="amount" :value="`Amount (${group.currency})`" />
                <TextInput
                    id="amount"
                    v-model="form.amount"
                    inputmode="decimal"
                    class="mt-1 block w-full"
                    placeholder="0.00"
                    required
                />
                <InputError class="mt-2" :message="form.errors.amount" />
            </div>

            <div>
                <InputLabel for="spent_on" value="Date" />
                <TextInput id="spent_on" v-model="form.spent_on" type="date" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.spent_on" />
            </div>
        </div>

        <div>
            <InputLabel for="paid_by" value="Paid by" />
            <select
                id="paid_by"
                v-model="form.paid_by"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option v-for="member in members" :key="member.id" :value="member.id">
                    {{ member.name }}
                </option>
            </select>
            <InputError class="mt-2" :message="form.errors.paid_by" />
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-gray-700">Split</legend>
            <div class="mt-2 flex flex-wrap gap-4">
                <label v-for="type in splitTypes" :key="type.value" class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.split_type"
                        type="radio"
                        :value="type.value"
                        class="text-indigo-600 focus:ring-indigo-500"
                    />
                    {{ type.label }}
                </label>
            </div>
        </fieldset>

        <div v-if="form.split_type === 'equal'" class="space-y-2">
            <label v-for="member in members" :key="member.id" class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.participants"
                    type="checkbox"
                    :value="member.id"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                {{ member.name }}
            </label>
            <InputError :message="form.errors.participants" />
            <template v-for="(message, key) in form.errors" :key="key">
                <InputError v-if="key.startsWith('participants.')" :message="message" />
            </template>
        </div>

        <div v-else class="space-y-2">
            <div v-for="member in members" :key="member.id" class="flex items-center gap-4">
                <span class="w-40 text-sm text-gray-700">{{ member.name }}</span>
                <TextInput
                    v-if="form.split_type === 'exact'"
                    v-model="form.shares[member.id]"
                    inputmode="decimal"
                    class="w-32"
                    placeholder="0.00"
                />
                <div v-else class="flex items-center gap-1">
                    <TextInput v-model="form.percents[member.id]" inputmode="decimal" class="w-24" placeholder="0" />
                    <span class="text-sm text-gray-500">%</span>
                </div>
            </div>
            <p class="text-sm text-gray-500">{{ remaining }}</p>
            <InputError :message="form.split_type === 'exact' ? form.errors.shares : form.errors.percents" />
        </div>

        <div class="flex items-center gap-4">
            <PrimaryButton :disabled="form.processing">Save expense</PrimaryButton>
            <Link :href="route('groups.show', group.id)" class="text-sm text-gray-600 hover:text-gray-900">
                Cancel
            </Link>
        </div>
    </form>
</template>
