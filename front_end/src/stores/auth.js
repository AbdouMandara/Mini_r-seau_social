import { defineStore } from 'pinia';
import api from '@/utils/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null, // Don't load from localStorage
        token: localStorage.getItem('auth_token') || null,
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async register(userData) {
            this.loading = true;
            this.error = null;
            try {
                // formData because of images
                const response = await api.post('/register', userData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.setAuth(response.data.user, response.data.access_token);
                return response.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Erreur lors de l’inscription';
                throw err;
            } finally {
                this.loading = false;
            }
        },
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post('/login', credentials);
                this.setAuth(response.data.user, response.data.access_token);
                return response.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Les identifiants sont incorrects';
                throw err;
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            try {
                await api.post('/logout');
            } catch (err) {
                console.error('Logout error', err);
            } finally {
                this.clearAuth();
            }
        },
        setAuth(user, token) {
            this.user = user;
            this.token = token;
            // Removed user storage and clean up old
            localStorage.setItem('auth_token', token);
            localStorage.removeItem('user');
        },
        clearAuth() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('auth_token'); 
            localStorage.removeItem('user'); 
        },
        async fetchProfile() {
            try {
                const response = await api.get('/user');
                this.user = response.data;
                // No storage here
            } catch (err) {
                this.clearAuth();
            }
        }
    }
});
