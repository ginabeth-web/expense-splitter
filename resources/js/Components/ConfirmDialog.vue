<script setup>
const open = defineModel({ type: Boolean, default: false });

defineProps({
    title: { type: String, default: 'Are you sure?' },
    text: { type: String, default: '' },
    confirmText: { type: String, default: 'Delete' },
    confirmColor: { type: String, default: 'error' },
});

const emit = defineEmits(['confirm']);

const confirm = () => {
    open.value = false;
    emit('confirm');
};
</script>

<template>
    <v-dialog v-model="open" max-width="420">
        <v-card :title="title" :text="text">
            <v-card-actions>
                <v-spacer />
                <v-btn variant="text" @click="open = false">Cancel</v-btn>
                <v-btn :color="confirmColor" variant="flat" @click="confirm">{{ confirmText }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
