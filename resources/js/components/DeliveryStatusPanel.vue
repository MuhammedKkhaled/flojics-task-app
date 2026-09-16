<script setup>
import StatusBadge from './StatusBadge.vue';

defineProps({
    deliveries: {
        type: Array,
        default: () => [],
    },
    polling: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section class="panel delivery-panel" aria-labelledby="delivery-heading" aria-live="polite">
        <div class="section-heading compact">
            <div>
                <p class="eyebrow">Delivery activity</p>
                <h2 id="delivery-heading">Notification status</h2>
            </div>
            <span v-if="polling" class="live-indicator"><i /> Updating</span>
        </div>

        <p v-if="deliveries.length === 0" class="muted">No notifications have been created.</p>

        <div v-else class="delivery-list">
            <article v-for="delivery in deliveries" :key="delivery.uuid" class="delivery-row">
                <div class="delivery-summary">
                    <strong>{{ delivery.channel }}</strong>
                    <span>{{ delivery.recipient }}</span>
                </div>
                <div class="delivery-result">
                    <StatusBadge :value="delivery.status" />
                    <span>{{ delivery.attempts }} / {{ delivery.max_attempts }} attempts</span>
                </div>
                <p v-if="delivery.last_error" class="delivery-error">
                    {{ delivery.last_error_code ? `${delivery.last_error_code}: ` : '' }}{{ delivery.last_error }}
                </p>
            </article>
        </div>
    </section>
</template>
