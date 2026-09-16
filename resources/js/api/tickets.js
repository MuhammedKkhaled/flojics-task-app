import apiClient from './client';

export const getTickets = async () => {
    const response = await apiClient.get('/tickets');

    return response.data.data;
};

export const getTicket = async (ticketId) => {
    const response = await apiClient.get(`/tickets/${ticketId}`);

    return response.data.data;
};

export const getNotificationChannels = async () => {
    const response = await apiClient.get('/notification-channels');

    return response.data.data;
};

export const escalateTicket = async (ticketId, payload) => {
    const response = await apiClient.post(`/tickets/${ticketId}/escalate`, payload);

    return response.data.data;
};
