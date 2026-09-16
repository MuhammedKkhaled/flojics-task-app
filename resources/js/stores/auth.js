import { defineStore } from 'pinia';
import { login as loginRequest, logout as logoutRequest } from '../api/auth';
import { TOKEN_STORAGE_KEY } from '../api/client';

const USER_STORAGE_KEY = 'flojics_user';

const storedUser = () => {
    try {
        return JSON.parse(window.localStorage.getItem(USER_STORAGE_KEY));
    } catch {
        return null;
    }
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: window.localStorage.getItem(TOKEN_STORAGE_KEY),
        user: storedUser(),
        loading: false,
        error: '',
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
    },

    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = '';

            try {
                const session = await loginRequest(credentials);

                this.token = session.token;
                this.user = session.user;
                window.localStorage.setItem(TOKEN_STORAGE_KEY, session.token);
                window.localStorage.setItem(
                    USER_STORAGE_KEY,
                    JSON.stringify(session.user),
                );
            } catch (error) {
                this.error =
                    error.response?.data?.errors?.email?.[0] ??
                    error.response?.data?.message ??
                    'Sign in failed. Please try again.';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await logoutRequest();
                }
            } catch {
                // Local sign-out must still succeed if the token has expired.
            } finally {
                this.token = null;
                this.user = null;
                window.localStorage.removeItem(TOKEN_STORAGE_KEY);
                window.localStorage.removeItem(USER_STORAGE_KEY);
            }
        },
    },
});
