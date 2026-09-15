<script setup>
import { onMounted, ref } from 'vue';

const tickets = ref([]);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        const response = await window.axios.get('/api/tickets');
        tickets.value = response.data.data;
    } catch (requestError) {
        error.value = requestError.response?.data?.message ?? 'Tickets could not be loaded.';
    } finally {
        loading.value = false;
    }
});

const titleCase = (value) => value.replaceAll('_', ' ');
</script>

<template>
    <main class="shell">
        <header class="page-header">
            <div>
                <p class="eyebrow">Flojics Help Desk</p>
                <h1>Support tickets</h1>
                <p class="subtitle">Phase 0 baseline · Laravel, Sanctum, Horizon and Vue are connected.</p>
            </div>
            <span class="ticket-count">{{ tickets.length }} tickets</span>
        </header>

        <section class="ticket-panel" aria-live="polite">
            <p v-if="loading" class="state-message">Loading tickets…</p>
            <p v-else-if="error" class="state-message error">{{ error }}</p>
            <p v-else-if="tickets.length === 0" class="state-message">No tickets found.</p>

            <div v-else class="ticket-list">
                <article v-for="ticket in tickets" :key="ticket.id" class="ticket-card">
                    <div class="ticket-main">
                        <span class="ticket-id">#{{ ticket.id }}</span>
                        <h2>{{ ticket.subject }}</h2>
                        <p>{{ ticket.customer }} · {{ ticket.agent ?? 'Unassigned' }}</p>
                    </div>
                    <div class="badges">
                        <span :class="['badge', `priority-${ticket.priority}`]">
                            {{ titleCase(ticket.priority) }}
                        </span>
                        <span :class="['badge', `status-${ticket.status}`]">
                            {{ titleCase(ticket.status) }}
                        </span>
                    </div>
                </article>
            </div>
        </section>
    </main>
</template>
