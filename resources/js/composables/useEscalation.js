import { ref } from 'vue';
import { useTicketsStore } from '../stores/tickets';

const responseMessage = (requestError) => {
    const validationErrors = requestError.response?.data?.errors;
    const firstValidationError = validationErrors
        ? Object.values(validationErrors).flat().find(Boolean)
        : null;

    if (firstValidationError) {
        return firstValidationError;
    }

    if (requestError.response?.status === 401) {
        return 'Your session has expired. Sign in and try again.';
    }

    return requestError.response?.data?.message ?? 'The ticket could not be escalated.';
};

export const useEscalation = () => {
    const ticketsStore = useTicketsStore();
    const loading = ref(false);
    const error = ref('');

    const escalate = async (ticketId, channels, reason = '') => {
        loading.value = true;
        error.value = '';

        try {
            return await ticketsStore.escalate(ticketId, {
                channels,
                reason: reason.trim() || null,
            });
        } catch (requestError) {
            error.value = responseMessage(requestError);
            return null;
        } finally {
            loading.value = false;
        }
    };

    return { error, escalate, loading };
};
