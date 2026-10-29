<script setup>
defineProps({
    balances: Array,
});
</script>

<template>
    <ul class="divide-y divide-gray-100">
        <li v-for="balance in balances" :key="balance.user_id" class="flex justify-between py-2 text-sm">
            <span class="text-gray-900">{{ balance.name }}</span>
            <span v-if="balance.cents > 0" class="text-green-700">is owed {{ balance.display }}</span>
            <span v-else-if="balance.cents < 0" class="text-red-700">owes {{ balance.display }}</span>
            <span v-else class="text-gray-500">settled up</span>
        </li>
    </ul>
</template>
