import axios from 'axios';

export const TOKEN_STORAGE_KEY = 'flojics_access_token';

const apiClient = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

apiClient.interceptors.request.use((request) => {
    const token = window.localStorage.getItem(TOKEN_STORAGE_KEY);

    if (token) {
        request.headers.Authorization = `Bearer ${token}`;
    }

    return request;
});

export default apiClient;
