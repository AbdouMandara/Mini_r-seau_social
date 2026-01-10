<template>
  <Transition name="fade">
    <div v-if="show" class="drawer-overlay" @click.self="$emit('close')">
      <Transition name="slide">
        <div v-if="show" class="notif-drawer card mobile-menu-content">
          <div class="drawer-header">
            <h3>Menu</h3>
            <button class="close-btn" @click="$emit('close')">
              <span class="material-symbols-rounded">close</span>
            </button>
          </div>
          <div class="drawer-body">
            <div class="menu-item-mobile" @click="$emit('open-notifs')">
              <div class="label-with-icon">
                <span class="material-symbols-rounded">notifications</span>
                Notifications
              </div>
              <span v-if="unreadCount > 0" class="badge-count">{{ unreadCount }}</span>
            </div>
            
            <!-- Admin Menu Items -->
            <template v-if="authStore.user?.is_admin || authStore.user?.data?.is_admin">
              <div v-if="route.name !== 'admin-dashboard'" class="menu-item-mobile" @click="navigateTo('/admin/dashboard')">
                <div class="label-with-icon">
                  <span class="material-symbols-rounded">dashboard</span>
                  Dashboard
                </div>
              </div>
              <div v-if="route.name !== 'admin-feedbacks'" class="menu-item-mobile" @click="navigateTo('/admin/feedbacks')">
                <div class="label-with-icon">
                  <span class="material-symbols-rounded">feedback</span>
                  Feedbacks
                </div>
              </div>
              <div v-if="route.name !== 'admin-activities'" class="menu-item-mobile" @click="navigateTo('/admin/activites')">
                <div class="label-with-icon">
                  <span class="material-symbols-rounded">history</span>
                  Activités
                </div>
              </div>
              <div v-if="route.name !== 'admin-reports'" class="menu-item-mobile" @click="navigateTo('/admin/signalements')">
                <div class="label-with-icon">
                  <span class="material-symbols-rounded">flag</span>
                  Signalements
                </div>
              </div>
              <div v-if="route.name !== 'admin-badges'" class="menu-item-mobile" @click="navigateTo('/admin/badges')">
                <div class="label-with-icon">
                  <span class="material-symbols-rounded">stars</span>
                  Badges
                </div>
              </div>
            </template>

            <div class="menu-item-mobile" @click="themeStore.toggleTheme">
              <div class="label-with-icon">
                <span class="material-symbols-rounded">{{ themeStore.isDark ? 'light_mode' : 'dark_mode' }}</span>
                Mode {{ themeStore.isDark ? 'Clair' : 'Sombre' }}
              </div>
            </div>

            <div v-if="!route.path.startsWith('/admin')" class="menu-item-mobile" @click="navigateToFeedback">
              <div class="label-with-icon">
                <span class="material-symbols-rounded">rate_review</span>
                Donner un avis
              </div>
            </div>

            <div class="menu-item-mobile logout-item-mobile" @click="$emit('logout')">
              <div class="label-with-icon">
                <span class="material-symbols-rounded">logout</span>
                Déconnexion
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { useRouter, useRoute } from 'vue-router';

const props = defineProps({
  show: { type: Boolean, default: false },
  unreadCount: { type: Number, default: 0 }
});

const emit = defineEmits(['close', 'open-notifs', 'logout']);

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

// Handle scroll locking
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const userSlug = computed(() => {
  const user = authStore.user?.data || authStore.user;
  const name = user?.slug || user?.nom || '';
  return name.replace(/ /g, '_');
});

const navigateTo = (path) => {
  router.push(path);
  emit('close');
};

const navigateToFeedback = () => {
  router.push({ name: 'feedback', params: { nom_user: userSlug.value || 'user' } });
  emit('close');
};
</script>

<style scoped>
.notif-drawer {
  position: fixed;
  right: 0;
  width: 360px;
  max-width: 90vw;
  height: calc(100vh - 60px);
  z-index: 300;
  overflow-y: auto;
  border-radius: 0;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-color);
}

.drawer-header h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-color);
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--secondary-color);
  color: var(--text-color);
}

.drawer-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.menu-item-mobile {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.menu-item-mobile:hover {
  background: var(--secondary-color);
}

.label-with-icon {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
  color: var(--text-color);
}

.badge-count {
  background: var(--error);
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.logout-item-mobile {
  margin-top: 10px;
  border-top: 1px solid var(--border-color);
  padding-top: 15px;
  color: var(--error);
}

.logout-item-mobile .material-symbols-rounded {
  color: var(--error);
}

/* Overlay */
.drawer-overlay {
  position: fixed;
  top: 60px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 299;
  backdrop-filter: blur(2px);
}

/* Fade transition for overlay */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Slide transition for drawer */
.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}
</style>
