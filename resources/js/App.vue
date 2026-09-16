<script setup>
import { storeToRefs } from 'pinia';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const { isAuthenticated, user } = storeToRefs(authStore);

const signOut = async () => {
    await authStore.logout();
    await router.push({ name: 'login' });
};
</script>

<template>
    <div class="app-shell">
        <header class="app-header">
            <RouterLink class="brand" :to="isAuthenticated ? { name: 'tickets.index' } : { name: 'login' }">
                <span class="brand-mark">F</span>
                <span>
                    <strong>Flojics</strong>
                    <small>Help Desk</small>
                </span>
            </RouterLink>

            <div v-if="isAuthenticated" class="session">
                <span>{{ user?.name ?? 'Signed in' }}</span>
                <button class="link-button" type="button" @click="signOut">Sign out</button>
            </div>
        </header>

        <RouterView />
    </div>
</template>
