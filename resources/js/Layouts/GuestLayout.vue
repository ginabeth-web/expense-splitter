<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <v-app>
        <v-main class="d-flex align-center">
            <v-container style="max-width: 460px">
                <div class="text-center mb-6">
                    <Link href="/" class="guest-logo">
                        <v-icon icon="mdi-cash-multiple" color="primary" size="56" />
                        <div class="text-h5 font-weight-bold mt-2">Expense Splitter</div>
                    </Link>
                </div>
                <v-card class="pa-6" elevation="2">
                    <slot />
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>

<style scoped>
.guest-logo {
    color: inherit;
    text-decoration: none;
}
</style>
