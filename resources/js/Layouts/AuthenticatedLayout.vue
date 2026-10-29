<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDisplay } from 'vuetify';

const page = usePage();
const { mdAndUp } = useDisplay();

const drawer = ref(null);
const flash = ref(false);

watch(
    () => page.props.flash.success,
    (message) => {
        flash.value = Boolean(message);
    },
    { immediate: true },
);

const navItems = [
    { title: 'Dashboard', icon: 'mdi-view-dashboard-outline', route: 'dashboard', active: 'dashboard' },
    { title: 'Groups', icon: 'mdi-account-group-outline', route: 'groups.index', active: 'groups.*' },
];

const logout = () => router.post(route('logout'));
</script>

<template>
    <v-app>
        <v-app-bar color="primary" flat>
            <v-app-bar-nav-icon v-if="!mdAndUp" @click="drawer = !drawer" />
            <v-app-bar-title>
                <Link :href="route('dashboard')" class="app-title">
                    <v-icon icon="mdi-cash-multiple" class="me-2" />
                    Expense Splitter
                </Link>
            </v-app-bar-title>

            <v-menu>
                <template #activator="{ props }">
                    <v-btn v-bind="props" variant="text" class="text-none" append-icon="mdi-chevron-down">
                        <v-avatar color="white" size="28" class="me-2 text-primary">
                            {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </v-avatar>
                        <span class="d-none d-sm-inline">{{ page.props.auth.user.name }}</span>
                    </v-btn>
                </template>
                <v-list density="compact" min-width="180">
                    <v-list-item
                        prepend-icon="mdi-account-outline"
                        title="Profile"
                        @click="router.visit(route('profile.edit'))"
                    />
                    <v-list-item prepend-icon="mdi-logout" title="Log out" @click="logout" />
                </v-list>
            </v-menu>
        </v-app-bar>

        <v-navigation-drawer v-model="drawer" :permanent="mdAndUp">
            <v-list nav>
                <v-list-item
                    v-for="item in navItems"
                    :key="item.route"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :active="route().current(item.active)"
                    color="primary"
                    @click="router.visit(route(item.route))"
                />
            </v-list>
        </v-navigation-drawer>

        <v-main>
            <v-container class="py-6" style="max-width: 1200px">
                <div v-if="$slots.header" class="d-flex flex-wrap align-center ga-4 mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </v-container>
        </v-main>

        <v-snackbar v-model="flash" color="success" location="top right" timeout="3000">
            {{ page.props.flash.success }}
        </v-snackbar>
    </v-app>
</template>

<style scoped>
.app-title {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
</style>
