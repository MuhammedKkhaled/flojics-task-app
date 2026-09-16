import { createRouter, createWebHistory } from 'vue-router';
import { TOKEN_STORAGE_KEY } from '../api/client';
import LoginPage from '../pages/LoginPage.vue';
import TicketShow from '../pages/TicketShow.vue';
import TicketsIndex from '../pages/TicketsIndex.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
            meta: { guest: true },
        },
        {
            path: '/tickets',
            name: 'tickets.index',
            component: TicketsIndex,
            meta: { requiresAuth: true },
        },
        {
            path: '/tickets/:id',
            name: 'tickets.show',
            component: TicketShow,
            meta: { requiresAuth: true },
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: { name: 'tickets.index' },
        },
    ],
});

router.beforeEach((to) => {
    const isAuthenticated = Boolean(
        window.localStorage.getItem(TOKEN_STORAGE_KEY),
    );

    if (to.meta.requiresAuth && !isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && isAuthenticated) {
        return { name: 'tickets.index' };
    }

    return true;
});

export default router;
