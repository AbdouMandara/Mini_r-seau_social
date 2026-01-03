import axios from 'axios';

export const BASE_URL = window.location.origin;

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
    }
});

api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 403 && error.response.data.is_blocked) {
            window.location.href = '/user_bloque';
        }
        return Promise.reject(error);
    }
);

export default api;