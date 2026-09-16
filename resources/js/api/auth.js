import apiClient from './client';

export const login = async (credentials) => {
    const response = await apiClient.post('/login', {
        ...credentials,
        device_name: 'flojics-spa',
    });

    return response.data;
};

export const logout = () => apiClient.delete('/logout');
