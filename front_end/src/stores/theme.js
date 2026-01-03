import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useThemeStore = defineStore('theme', () => {
    const isDark = ref(localStorage.getItem('pozterr_theme') === 'dark');

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        localStorage.setItem('pozterr_theme', isDark.value ? 'dark' : 'light');
        applyTheme();
    };

    const applyTheme = () => {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    // Initialize
    applyTheme();

    return {
        isDark,
        toggleTheme,
        applyTheme
    };
});
