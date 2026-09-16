import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TicketDetails from './TicketDetails.vue';

describe('TicketDetails', () => {
    it('renders the five required ticket fields', () => {
        const wrapper = mount(TicketDetails, {
            props: {
                ticket: {
                    id: 42,
                    subject: 'Production API is unavailable',
                    description: 'The customer cannot process requests.',
                    priority: 'urgent',
                    status: 'escalated',
                    escalated_at: '2026-09-16T10:00:00+00:00',
                    customer: 'Acme Ltd',
                    agent: 'Mona Hassan',
                },
            },
        });

        const text = wrapper.text();

        expect(text).toContain('Ticket #42');
        expect(text).toContain('Production API is unavailable');
        expect(text).toContain('urgent');
        expect(text).toContain('escalated');
        expect(text).toContain('Escalation date');
        expect(text).not.toContain('Not escalated');
    });
});
