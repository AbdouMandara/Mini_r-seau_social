import { defineStore } from 'pinia';
import api from '@/utils/api';

export const useAuthStore = defineStore('auth', {
        state: () => ({
        user: null,
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        async register(userData) {
            this.loading = true;
            this.error = null;

            try {
                await api.get('/sanctum/csrf-cookie', { baseURL: '/' });

                // If sending FormData (file upload), ensure multipart header is set
                const config = (userData instanceof FormData)
                    ? { headers: { 'Content-Type': 'multipart/form-data' } }
                    : {};

                const response = await api.post('/register', userData, config);

                if (!this.user && response.data.data) this.user = response.data.data; // Fallback

                localStorage.setItem('pozterr_logged_in', 'true');
                return response.data;

            } catch (err) {
                    // Log server response to help debugging validation errors (422)
                    console.error('Register error response:', err.response?.data || err);

                    this.error = err.response?.data?.message || 'Erreur lors de l’inscription';
                    throw err;
            } finally {
                this.loading = false;
            }
        },

        async login(credentials) {
            this.loading = true;
            this.error = null;
            localStorage.removeItem('pozterr_logged_in'); // Clean start

            try {
                await api.get('/sanctum/csrf-cookie', { baseURL: '/' });

                const response = await api.post('/login', credentials);

                // Handle potential Laravel API Resource wrapping ('data' key)
                const userData = response.data.user?.data || response.data.user;

                if (userData) {
                    this.user = userData;
                    localStorage.setItem('pozterr_logged_in', 'true');
                } else {
                    console.error('Login success but user data missing in response:', response.data);
                    // Do not set this.user to null here if it was already null
                }
                return response.data;

            } catch (err) {
                this.error =
                    err.response?.data?.message ||
                    'Identifiants incorrects';
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
                this.user = null;
                localStorage.removeItem('pozterr_logged_in');
                localStorage.removeItem('auth_token'); // Nettoyage du vieux token si présent
            }
        },

        async fetchProfile() {
            if (!localStorage.getItem('pozterr_logged_in')) {
                return;
            }

            try {
                const response = await api.get('/user');
                this.user = response.data.data || response.data;
            } catch (err) {
                this.user = null;
                if (err.response?.status === 401) {
                    localStorage.removeItem('pozterr_logged_in');
                }
            }
        },
    },
});
