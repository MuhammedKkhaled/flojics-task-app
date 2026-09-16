import { createPinia } from 'pinia';
import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { getNotificationChannels, getTicket } from '../api/tickets';
import TicketShow from '../pages/TicketShow.vue';
import DeliveryStatusPanel from './DeliveryStatusPanel.vue';

vi.mock('../api/tickets', () => ({
    escalateTicket: vi.fn(),
    getNotificationChannels: vi.fn(),
    getTicket: vi.fn(),
    getTickets: vi.fn(),
}));

const ticketWith = (status) => ({
    id: 1,
    subject: 'Production API is unavailable',
    description: 'Customer requests are failing.',
    priority: 'urgent',
    status: 'escalated',
    can_escalate: false,
    escalated_at: '2026-09-16T10:00:00+00:00',
    customer: 'Acme Ltd',
    agent: 'Mona Hassan',
    escalation: {
        id: 10,
        deliveries: [
            {
                uuid: 'delivery-1',
                channel: 'slack',
                recipient: '#support',
                status,
                attempts: status === 'pending' ? 1 : 2,
                max_attempts: 4,
                last_error: status === 'pending' ? 'Slack is unavailable.' : null,
                last_error_code: status === 'pending' ? 'slack_server_error' : null,
            },
        ],
    },
});

describe('DeliveryStatusPanel', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders delivery attempts and the latest error', () => {
        const wrapper = mount(DeliveryStatusPanel, {
            props: {
                deliveries: ticketWith('pending').escalation.deliveries,
                polling: true,
            },
        });

        expect(wrapper.text()).toContain('1 / 4 attempts');
        expect(wrapper.text()).toContain('slack_server_error: Slack is unavailable.');
        expect(wrapper.text()).toContain('Updating');
    });

    it('stops polling after no delivery remains pending', async () => {
        getTicket
            .mockResolvedValueOnce(ticketWith('pending'))
            .mockResolvedValue(ticketWith('sent'));
        getNotificationChannels.mockResolvedValue([
            { key: 'email', label: 'Email' },
            { key: 'slack', label: 'Slack' },
        ]);

        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/tickets/:id', name: 'tickets.show', component: TicketShow },
                { path: '/tickets', name: 'tickets.index', component: { template: '<div />' } },
            ],
        });
        await router.push('/tickets/1');
        await router.isReady();

        const wrapper = mount(TicketShow, {
            global: {
                plugins: [createPinia(), router],
            },
        });
        await flushPromises();

        expect(getTicket).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(3000);
        await flushPromises();

        expect(getTicket).toHaveBeenCalledTimes(2);
        expect(wrapper.text()).toContain('sent');

        await vi.advanceTimersByTimeAsync(9000);
        await flushPromises();

        expect(getTicket).toHaveBeenCalledTimes(2);
        wrapper.unmount();
    });
});
