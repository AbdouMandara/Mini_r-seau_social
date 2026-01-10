<template>
  <div class="app-wrapper">
    <!-- Main Header -->
    <MainHeader
      :unread-count="unreadCount"
      :is-blocked-page="isBlockedPage"
      :is-full-width-page="isFullWidthPage"
      @toggle-notifs="toggleNotifs"
      @toggle-mobile-menu="toggleMobileMenu"
      @logout="handleLogout"
    />

    <!-- Notifications Drawer -->
    <NotificationDrawer
      :show="showNotifs"
      :notifications="notifications"
      @close="showNotifs = false"
    />

    <!-- Mobile Menu Drawer -->
    <MobileMenu
      :show="showMobileMenu"
      :unread-count="unreadCount"
      @close="showMobileMenu = false"
      @open-notifs="openNotifsFromMobile"
      @logout="handleLogout"
    />

    <!-- Main Content -->
    <main class="main-container" :class="{ 'container': !isFullWidthPage }">
      <router-view />
    </main>

    <!-- Mobile Footer -->
    <MobileFooter :is-blocked-page="isBlockedPage" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import api from '@/utils/api';
import Swal from 'sweetalert2';
import MainHeader from '@/components/layout/MainHeader.vue';
import NotificationDrawer from '@/components/layout/NotificationDrawer.vue';
import MobileMenu from '@/components/layout/MobileMenu.vue';
import MobileFooter from '@/components/layout/MobileFooter.vue';

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

const showNotifs = ref(false);
const showMobileMenu = ref(false);
const notifications = ref([]);
const unreadCount = computed(() => notifications.value.filter(n => !n.is_read).length);
const prevUnreadCount = ref(0);

const isFullWidthPage = computed(() => {
  const fullWidthRoutes = ['login', 'register', 'blocked'];
  return fullWidthRoutes.includes(route.name);
});

const isBlockedPage = computed(() => route.name === 'blocked');

// Watch for user changes to refresh notifications
watch(() => authStore.user, (val) => {
  if (val) {
    fetchNotifications();
  } else {
    notifications.value = [];
  }
});

const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Déconnexion ?',
    text: "Voulez-vous vraiment vous déconnecter ?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#1877f2',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Oui, déconnexion',
    cancelButtonText: 'Annuler',
    background: themeStore.isDark ? '#161b22' : '#fff',
    color: themeStore.isDark ? '#e6edf3' : '#1c1e21'
  });

  if (result.isConfirmed) {
    showMobileMenu.value = false;
    showNotifs.value = false;
    await authStore.logout();
    Swal.fire({
      title: 'Déconnecté',
      text: 'À bientôt sur !Pozterr !',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  }
   router.push('/login');
    return;
};

const fetchNotifications = async () => {
  if (!authStore.isAuthenticated) return;
  try {
    const res = await api.get('/notifications');
    const newNotifs = res.data.data || res.data;
    if (!Array.isArray(newNotifs)) return;
    const newUnread = newNotifs.filter(n => !n.is_read).length;

    if (newUnread > prevUnreadCount.value) {
      const latest = newNotifs.find(n => !n.is_read);
      if (latest) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'info',
          title: latest.type === 'follow' ? `${latest.author?.nom} a commencé à vous suivre` :
                 latest.type === 'follow_back' ? `${latest.author?.nom} vous a suivi en retour` :

                 latest.type === 'report' ? `Nouveau signalement de ${latest.author?.nom}` :
                 latest.type === 'new_user' ? `Nouvelle inscription : ${latest.author?.nom}` :
                 `${latest.author?.nom} a interagi avec votre post`,
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          background: themeStore.isDark ? '#161b22' : '#fff',
          color: themeStore.isDark ? '#e6edf3' : '#1c1e21'
        });
      }
    }

    notifications.value = newNotifs;
    prevUnreadCount.value = newUnread;
  } catch (err) {
    console.error('Fetch notifications error', err);
  }
};

const toggleNotifs = async () => {
  showMobileMenu.value = false;
  showNotifs.value = !showNotifs.value;
  if (showNotifs.value && unreadCount.value > 0) {
    try {
      await api.post('/notifications/read');
      notifications.value.forEach(n => n.is_read = true);
    } catch (err) {
      console.error('Mark as read error', err);
    }
  }
};

const toggleMobileMenu = () => {
  showNotifs.value = false;
  showMobileMenu.value = !showMobileMenu.value;
};

const openNotifsFromMobile = async () => {
  showMobileMenu.value = false;
  showNotifs.value = true;
  // Mark notifications as read when opening from mobile
  if (unreadCount.value > 0) {
    try {
      await api.post('/notifications/read');
      notifications.value.forEach(n => n.is_read = true);
    } catch (err) {
      console.error('Mark as read error', err);
    }
  }
};

onMounted(() => {
  authStore.fetchProfile();
  fetchNotifications();
  // Poll notifications every minute
  setInterval(fetchNotifications, 60000);
});
</script>

<style>
.app-wrapper {
  padding-bottom: env(safe-area-inset-bottom);
  background-color: var(--secondary-color);
  min-height: 100vh;
}

.main-container {
  min-height: calc(100vh - 60px);
}

@media (min-width: 768px) {
  .main-container {
    padding-bottom: 20px;
  }
}
</style>
