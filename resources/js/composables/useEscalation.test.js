import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { escalateTicket } from '../api/tickets';
import { useEscalation } from './useEscalation';

vi.mock('../api/tickets', () => ({
    escalateTicket: vi.fn(),
    getNotificationChannels: vi.fn(),
    getTicket: vi.fn(),
    getTickets: vi.fn(),
}));

describe('useEscalation', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('posts selected channels and exposes a 422 validation error', async () => {
        escalateTicket.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        channels: ['The selected channel is invalid.'],
                    },
                },
            },
        });

        const { error, escalate, loading } = useEscalation();
        const result = await escalate(19, ['email', 'slack'], 'Customer is blocked.');

        expect(escalateTicket).toHaveBeenCalledWith(19, {
            channels: ['email', 'slack'],
            reason: 'Customer is blocked.',
        });
        expect(result).toBeNull();
        expect(error.value).toBe('The selected channel is invalid.');
        expect(loading.value).toBe(false);
    });
});
