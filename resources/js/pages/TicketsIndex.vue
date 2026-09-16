<script setup>
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import StatusBadge from '../components/StatusBadge.vue';
import { useTicketsStore } from '../stores/tickets';

const ticketsStore = useTicketsStore();
const { listError, loadingList, tickets } = storeToRefs(ticketsStore);

onMounted(() => ticketsStore.loadTickets());
</script>

<template>
    <main class="page-shell">
        <header class="page-heading">
            <div>
                <p class="eyebrow">Ticket queue</p>
                <h1>Support tickets</h1>
                <p class="subtitle">Review a ticket and notify the right response channels.</p>
            </div>
            <span class="ticket-count">{{ tickets.length }} tickets</span>
        </header>

        <section class="panel ticket-panel" aria-live="polite">
            <p v-if="loadingList" class="state-message">Loading tickets…</p>
            <p v-else-if="listError" class="state-message error">{{ listError }}</p>
            <p v-else-if="tickets.length === 0" class="state-message">No tickets found.</p>

            <div v-else class="ticket-list">
                <RouterLink
                    v-for="ticket in tickets"
                    :key="ticket.id"
                    class="ticket-card"
                    :to="{ name: 'tickets.show', params: { id: ticket.id } }"
                >
                    <div class="ticket-main">
                        <span class="ticket-id">#{{ ticket.id }}</span>
                        <h2>{{ ticket.subject }}</h2>
                        <p>{{ ticket.customer }} · {{ ticket.agent ?? 'Unassigned' }}</p>
                    </div>
                    <div class="badges">
                        <StatusBadge :value="ticket.priority" kind="priority" />
                        <StatusBadge :value="ticket.status" />
                    </div>
                </RouterLink>
            </div>
        </section>
    </main>
</template>
