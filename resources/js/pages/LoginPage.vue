<script setup>
import { storeToRefs } from 'pinia';
import { reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const { error, loading } = storeToRefs(authStore);
const credentials = reactive({
    email: 'agent@flojics.test',
    password: 'password',
});

const submit = async () => {
    try {
        await authStore.login(credentials);

        const redirect =
            typeof route.query.redirect === 'string' &&
            route.query.redirect.startsWith('/')
                ? route.query.redirect
                : '/tickets';

        await router.push(redirect);
    } catch {
        // The store exposes the API error next to the form.
    }
};
</script>

<template>
    <main class="login-shell">
        <section class="panel login-panel">
            <p class="eyebrow">Reviewer access</p>
            <h1>Sign in to the help desk</h1>
            <p class="subtitle">
                Use the seeded agent account to review ticket escalation.
            </p>

            <form class="login-form" @submit.prevent="submit">
                <label class="field">
                    <span>Email</span>
                    <input
                        v-model="credentials.email"
                        type="email"
                        autocomplete="username"
                        required
                    />
                </label>

                <label class="field">
                    <span>Password</span>
                    <input
                        v-model="credentials.password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                </label>

                <p v-if="error" class="form-error" role="alert">{{ error }}</p>

                <button
                    class="primary-button"
                    type="submit"
                    :disabled="loading"
                >
                    {{ loading ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>
        </section>
    </main>
</template>
