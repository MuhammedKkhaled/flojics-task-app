import { defineStore } from 'pinia';
import {
    escalateTicket as escalateTicketRequest,
    getNotificationChannels,
    getTicket,
    getTickets,
} from '../api/tickets';

export const useTicketsStore = defineStore('tickets', {
    state: () => ({
        tickets: [],
        current: null,
        channels: [],
        loadingList: false,
        loadingCurrent: false,
        loadingChannels: false,
        listError: '',
        currentError: '',
        channelError: '',
    }),

    actions: {
        async loadTickets() {
            this.loadingList = true;
            this.listError = '';

            try {
                this.tickets = await getTickets();
            } catch (error) {
                this.listError = error.response?.data?.message ?? 'Tickets could not be loaded.';
            } finally {
                this.loadingList = false;
            }
        },

        async loadTicket(ticketId, { background = false } = {}) {
            if (!background) {
                this.loadingCurrent = true;
                this.current = null;
            }

            this.currentError = '';

            try {
                const ticket = await getTicket(ticketId);
                this.current = ticket;

                const index = this.tickets.findIndex(({ id }) => id === ticket.id);
                if (index !== -1) {
                    this.tickets[index] = ticket;
                }

                return ticket;
            } catch (error) {
                if (!background) {
                    this.currentError = error.response?.status === 404
                        ? 'Ticket not found.'
                        : error.response?.data?.message ?? 'Ticket details could not be loaded.';
                }

                throw error;
            } finally {
                if (!background) {
                    this.loadingCurrent = false;
                }
            }
        },

        async loadChannels() {
            if (this.channels.length > 0) {
                return;
            }

            this.loadingChannels = true;
            this.channelError = '';

            try {
                this.channels = await getNotificationChannels();
            } catch (error) {
                this.channelError = error.response?.data?.message
                    ?? 'Notification channels could not be loaded.';
            } finally {
                this.loadingChannels = false;
            }
        },

        async escalate(ticketId, payload) {
            const escalation = await escalateTicketRequest(ticketId, payload);

            if (this.current?.id === Number(ticketId)) {
                this.current = {
                    ...this.current,
                    status: 'escalated',
                    can_escalate: false,
                    escalated_at: escalation.escalated_at,
                    escalation,
                };
            }

            try {
                await this.loadTicket(ticketId, { background: true });
            } catch {
                // The confirmed API response above remains the source of truth.
            }

            return escalation;
        },
    },
});
