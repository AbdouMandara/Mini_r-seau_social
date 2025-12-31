import axios from 'axios';

export const BASE_URL = 'http://localhost:8000';

const api = axios.create({
    baseURL: `${BASE_URL}/api`,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
});

api.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
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
