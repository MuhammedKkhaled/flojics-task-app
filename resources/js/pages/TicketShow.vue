<script setup>
import { storeToRefs } from 'pinia';
import { computed, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeliveryStatusPanel from '../components/DeliveryStatusPanel.vue';
import EscalateButton from '../components/EscalateButton.vue';
import TicketDetails from '../components/TicketDetails.vue';
import { usePolling } from '../composables/usePolling';
import { useTicketsStore } from '../stores/tickets';

const route = useRoute();
const ticketsStore = useTicketsStore();
const {
    channelError,
    channels,
    current: ticket,
    currentError,
    loadingChannels,
    loadingCurrent,
} = storeToRefs(ticketsStore);

const ticketId = computed(() => route.params.id);
const deliveries = computed(() => ticket.value?.escalation?.deliveries ?? []);
const hasPendingDeliveries = computed(() =>
    deliveries.value.some(({ status }) => status === 'pending'),
);

const refreshTicket = () =>
    ticketsStore.loadTicket(ticketId.value, { background: true });
const { isPolling, start, stop } = usePolling(refreshTicket, 3000);

watch(
    ticketId,
    async (id) => {
        stop();
        await Promise.allSettled([
            ticketsStore.loadTicket(id),
            ticketsStore.loadChannels(),
        ]);
    },
    { immediate: true },
);

watch(
    hasPendingDeliveries,
    (pending) => {
        if (pending) {
            start();
        } else {
            stop();
        }
    },
    { immediate: true },
);
</script>

<template>
    <main class="page-shell">
        <RouterLink class="back-link" :to="{ name: 'tickets.index' }"
            >← Back to tickets</RouterLink
        >

        <p v-if="loadingCurrent" class="state-message">Loading ticket…</p>
        <section v-else-if="currentError" class="panel">
            <p class="state-message error">{{ currentError }}</p>
        </section>

        <div v-else-if="ticket" class="ticket-layout">
            <TicketDetails :ticket="ticket" />

            <EscalateButton
                :ticket="ticket"
                :channels="channels"
                :channels-loading="loadingChannels"
                :channel-error="channelError"
            />

            <DeliveryStatusPanel
                v-if="ticket.escalation"
                :deliveries="deliveries"
                :polling="isPolling"
            />
        </div>
    </main>
</template>
