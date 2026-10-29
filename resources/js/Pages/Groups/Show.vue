<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
});

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
                <div class="space-y-6 lg:col-span-2"></div>

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
    </AuthenticatedLayout>
</template>
