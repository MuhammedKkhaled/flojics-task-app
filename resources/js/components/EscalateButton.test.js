import { createPinia } from 'pinia';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { escalateTicket } from '../api/tickets';
import EscalateButton from './EscalateButton.vue';

vi.mock('../api/tickets', () => ({
    escalateTicket: vi.fn(),
    getNotificationChannels: vi.fn(),
    getTicket: vi.fn(),
    getTickets: vi.fn(),
}));

const ticket = {
    id: 7,
    can_escalate: true,
};

const channels = [
    { key: 'email', label: 'Email' },
    { key: 'slack', label: 'Slack' },
];

const mountButton = (props = {}) => mount(EscalateButton, {
    props: {
        ticket,
        channels,
        ...props,
    },
    global: {
        plugins: [createPinia()],
    },
});

describe('EscalateButton', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('is disabled for tickets that cannot be escalated', () => {
        const wrapper = mountButton({
            ticket: { ...ticket, can_escalate: false },
        });

        expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
        expect(wrapper.text()).toContain('cannot be escalated');
    });

    it('is disabled when no channel is selected', async () => {
        const wrapper = mountButton();

        for (const checkbox of wrapper.findAll('input[type="checkbox"]')) {
            await checkbox.setValue(false);
        }

        expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    });

    it('is disabled while escalation is in flight', async () => {
        escalateTicket.mockReturnValue(new Promise(() => {}));
        const wrapper = mountButton();

        await wrapper.get('form').trigger('submit');
        await flushPromises();

        const button = wrapper.get('button[type="submit"]');
        expect(button.attributes('disabled')).toBeDefined();
        expect(button.text()).toBe('Escalating…');
    });
});
