<template>
  <nav v-if="authStore.user && !route.path.startsWith('/admin') && !isBlockedPage" class="mobile-footer">
    <div class="footer-grid">
      <router-link :to="`/${userSlug}/home`" class="footer-item">
        <span class="material-symbols-rounded">home</span>
      </router-link>
      <router-link :to="`/${userSlug}/add_post`" class="footer-item">
        <div class="plus-btn">
          <span class="material-symbols-rounded">add</span>
        </div>
      </router-link>
      <router-link :to="`/${userSlug}/profil`" class="footer-item">
        <div class="mini-avatar-container">
          <img :src="profileImageUrl" class="mini-avatar" @error="handleImgError" />
        </div>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute } from 'vue-router';
import { BASE_URL } from '@/utils/api';

defineProps({
  isBlockedPage: { type: Boolean, default: false }
});

const authStore = useAuthStore();
const route = useRoute();

const userSlug = computed(() => {
  const user = authStore.user?.data || authStore.user;
  if (!user) return '';
  const name = user.slug || user.nom || '';
  return name.replace(/ /g, '_');
});

const profileImageUrl = computed(() => {
  const user = authStore.user?.data || authStore.user;
  if (user?.photo_profil) {
    if (user.photo_profil.startsWith('http')) return user.photo_profil;
    return `${BASE_URL}/storage/${user.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + (user?.nom || 'User');
});

const handleImgError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=' + (authStore.user?.nom || 'User');
};
</script>

<style scoped>
.mobile-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--card-bg);
  border-top: 1px solid var(--border-color);
  height: 65px;
  z-index: 100;
  display: block;
  width: 100%;
}

.footer-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  height: 100%;
}

.footer-item {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  text-decoration: none;
  transition: all 0.2s;
}

.footer-item:hover {
  color: var(--primary-color);
}

.footer-item.router-link-active {
  color: var(--primary-color);
}

.plus-btn {
  width: 50px;
  height: 50px;
  background: var(--primary-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}

.mini-avatar-container {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  overflow: hidden;
}

.mini-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (max-width: 480px) {
  .mobile-footer {
    height: 56px;
  }
  .plus-btn {
    width: 42px;
    height: 42px;
  }
  .mini-avatar-container {
    width: 32px;
    height: 32px;
  }
  .material-symbols-rounded {
    font-size: 20px;
  }
}

@media (min-width: 768px) {
  .mobile-footer {
    display: none !important;
  }
}
</style>
