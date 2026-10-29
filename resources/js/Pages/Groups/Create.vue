<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    currency: 'PHP',
});
</script>

<template>
    <Head title="New group" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                New group
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <form
                    @submit.prevent="form.post(route('groups.store'))"
                    class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            class="mt-1 block w-full"
                            placeholder="Baguio Trip"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="currency" value="Currency code" />
                        <TextInput
                            id="currency"
                            v-model="form.currency"
                            class="mt-1 block w-32 uppercase"
                            maxlength="3"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.currency" />
                    </div>

                    <PrimaryButton :disabled="form.processing">Create group</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
