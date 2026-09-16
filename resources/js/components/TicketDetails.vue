<script setup>
import StatusBadge from './StatusBadge.vue';

defineProps({
    ticket: {
        type: Object,
        required: true,
    },
});

const displayDate = (value) => value
    ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Not escalated';
</script>

<template>
    <section class="panel ticket-details" aria-labelledby="ticket-heading">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Ticket #{{ ticket.id }}</p>
                <h1 id="ticket-heading">{{ ticket.subject }}</h1>
            </div>
            <div class="badges">
                <StatusBadge :value="ticket.priority" kind="priority" />
                <StatusBadge :value="ticket.status" />
            </div>
        </div>

        <p class="ticket-description">{{ ticket.description }}</p>

        <dl class="detail-grid">
            <div>
                <dt>Ticket ID</dt>
                <dd>#{{ ticket.id }}</dd>
            </div>
            <div>
                <dt>Priority</dt>
                <dd>{{ ticket.priority.replaceAll('_', ' ') }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>{{ ticket.status.replaceAll('_', ' ') }}</dd>
            </div>
            <div>
                <dt>Escalation date</dt>
                <dd>{{ displayDate(ticket.escalated_at) }}</dd>
            </div>
            <div>
                <dt>Customer</dt>
                <dd>{{ ticket.customer ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt>Assigned agent</dt>
                <dd>{{ ticket.agent ?? 'Unassigned' }}</dd>
            </div>
        </dl>
    </section>
</template>
