<script setup>
defineProps({
    balances: Array,
});
</script>

<template>
    <v-list density="compact" class="py-0">
        <v-list-item v-for="balance in balances" :key="balance.user_id" class="px-0">
            <template #prepend>
                <v-avatar size="32" color="secondary" variant="tonal" class="me-3">
                    {{ balance.name.charAt(0).toUpperCase() }}
                </v-avatar>
            </template>
            <v-list-item-title>{{ balance.name }}</v-list-item-title>
            <template #append>
                <v-chip v-if="balance.cents > 0" color="success" size="small" variant="tonal">
                    is owed {{ balance.display }}
                </v-chip>
                <v-chip v-else-if="balance.cents < 0" color="error" size="small" variant="tonal">
                    owes {{ balance.display }}
                </v-chip>
                <v-chip v-else size="small" variant="tonal">settled up</v-chip>
            </template>
        </v-list-item>
    </v-list>
</template>
