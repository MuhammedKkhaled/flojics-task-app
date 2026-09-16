<script setup>
import { computed, ref, watch } from 'vue';
import { useEscalation } from '../composables/useEscalation';
import ChannelSelector from './ChannelSelector.vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    channels: {
        type: Array,
        required: true,
    },
    channelsLoading: {
        type: Boolean,
        default: false,
    },
    channelError: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['escalated']);
const selectedChannels = ref([]);
const reason = ref('');
const { error, escalate, loading } = useEscalation();

watch(
    () => props.channels,
    (channels) => {
        selectedChannels.value = channels.map(({ key }) => key);
    },
    { immediate: true },
);

const disabled = computed(() => (
    !props.ticket.can_escalate
    || props.channelsLoading
    || loading.value
    || selectedChannels.value.length === 0
));

const submit = async () => {
    const escalation = await escalate(
        props.ticket.id,
        selectedChannels.value,
        reason.value,
    );

    if (escalation) {
        reason.value = '';
        emit('escalated', escalation);
    }
};
</script>

<template>
    <section class="panel escalation-form" aria-labelledby="escalation-heading">
        <div class="section-heading compact">
            <div>
                <p class="eyebrow">Escalation</p>
                <h2 id="escalation-heading">Notify the response team</h2>
            </div>
        </div>

        <p v-if="!ticket.can_escalate" class="notice">
            This ticket cannot be escalated in its current status.
        </p>

        <form @submit.prevent="submit">
            <p v-if="channelsLoading" class="muted">Loading notification channels…</p>
            <p v-else-if="channelError" class="form-error" role="alert">{{ channelError }}</p>
            <ChannelSelector
                v-else
                v-model="selectedChannels"
                :channels="channels"
                :disabled="loading || !ticket.can_escalate"
            />

            <label class="field">
                <span>Reason <small>(optional)</small></span>
                <textarea
                    v-model="reason"
                    maxlength="1000"
                    rows="4"
                    placeholder="Why does this ticket need attention?"
                    :disabled="loading || !ticket.can_escalate"
                />
            </label>

            <p v-if="error" class="form-error" role="alert">{{ error }}</p>

            <button class="primary-button" type="submit" :disabled="disabled">
                {{ loading ? 'Escalating…' : 'Escalate ticket' }}
            </button>
        </form>
    </section>
</template>
