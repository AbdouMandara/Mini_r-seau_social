<template>
  <header class="main-header" v-if="!isBlockedPage">
    <div class="header-content" :class="{ 'container': !isFullWidthPage }">
      <h1 class="logo clickable" @click="goHome">!Pozterr</h1>
      
      <div v-if="authStore.isAuthenticated && authStore.user" class="header-actions">
        <!-- Barre de recherche pour le Desktop , elle est cachée quand je suis coté admin -->
        <div v-if="authStore.user.role !== 'admin'" class="search-container desktop-only">
          <div class="search-input-wrapper">
            <span class="material-symbols-rounded search-icon">search</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              @input="handleSearch" 
              @focus="showSearchOverlay = true"
              class="search-input" 
              placeholder="Rechercher (@user ou #tag)..." 
            />
            <!-- La croix s'affiche quand j'ai déjà écrit au moins 1 caractère dans la barre de recherche -->
            <span v-if="searchQuery.length > 1" class="material-symbols-rounded clear-icon" @click="clearSearch">close</span>
          </div>

          <Transition name="fade">
            <div v-if="showSearchOverlay && (searchQuery.trim() !== '' || searching)" class="search-results-overlay card shadow-lg">
              <div v-if="searching" class="loader-container">
                <Loader />
              </div>
              <!-- Hashtag Search Result -->
              <div v-if="isHashtagSearch" class="hashtag-search-result" @click="navigateToHashtag">
                <div class="hashtag-icon">
                  <span class="material-symbols-rounded">tag</span>
                </div>
                <div class="hashtag-info">
                  <span class="hashtag-text">{{ searchQuery }}</span>
                  <span class="hashtag-hint">Voir tous les posts avec ce hashtag</span>
                </div>
              </div>
              <!-- User Results -->
              <div v-else-if="searchResults.length === 0" class="no-results">
                Aucun utilisateur trouvé
              </div>
              <div v-else class="results-list">
                <div 
                  v-for="user in searchResults" 
                  :key="user.id" 
                  class="search-result-item" 
                  @click="navigateToUserProfile(user)"
                >
                  <img :src="getUserAvatar(user)" :alt="`Photo de profil de ${user.nom}`" class="search-avatar" />
                  <span class="search-username">{{ user.nom }}</span>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Mobile Search Toggle -->
        <div v-if="authStore.user.role !== 'admin'" class="notif-btn mobile-only" @click="showMobileSearch = true">
          <span class="material-symbols-rounded">search</span>
        </div>

        <!-- Notification Bell (Desktop) -->
        <div class="notif-btn desktop-only" @click="$emit('toggle-notifs')">
          <span class="material-symbols-rounded">notifications</span>
          <span v-if="unreadCount > 0" class="notif-badge"></span>
        </div>

        <!-- Mobile Hamburger -->
        <div class="notif-btn mobile-only" @click="$emit('toggle-mobile-menu')">
          <span class="material-symbols-rounded">menu</span>
          <span v-if="unreadCount > 0" class="notif-badge"></span>
        </div>

        <!-- Mobile Search Overlay -->
        <Transition name="fade">
          <div v-if="showMobileSearch" class="mobile-search-overlay">
            <div class="mobile-search-header padd-inline">
              <button class="icon-btn" @click="closeMobileSearch">
                <span class="material-symbols-rounded">arrow_back</span>
              </button>
              <div class="search-input-wrapper flex-1">
                <input 
                  type="text" 
                  v-model="searchQuery" 
                  @input="handleSearch" 
                  class="search-input" 
                  placeholder="Rechercher (@user ou #tag)..." 
                  autoFocus
                />
                <span v-if="searchQuery.length > 0" class="material-symbols-rounded clear-icon" @click="clearSearch">close</span>
              </div>
            </div>
            
            <div class="search-results-content">
              <div v-if="searching" class="loader-container">
                <Loader />
              </div>
              <!-- Hashtag result for mobile -->
              <div v-if="isHashtagSearch" class="hashtag-search-result" @click="navigateToHashtag">
                <div class="hashtag-icon">
                  <span class="material-symbols-rounded">tag</span>
                </div>
                <div class="hashtag-info">
                  <span class="hashtag-text">{{ searchQuery }}</span>
                  <span class="hashtag-hint">Voir tous les posts avec ce hashtag</span>
                </div>
              </div>
              <div v-else-if="searchQuery.trim() !== '' && searchResults.length === 0" class="no-results">
                Aucun utilisateur trouvé
              </div>
              <div v-else class="results-list">
                <div 
                  v-for="user in searchResults" 
                  :key="user.id" 
                  class="search-result-item" 
                  @click="navigateToUserProfile(user)"
                >
                  <img :src="getUserAvatar(user)" :alt="`Photo de profil de ${user.nom}`" class="search-avatar" />
                  <div class="search-info">
                    <span class="search-username">{{ user.nom }}</span>
                    <span class="search-email">@{{ (user.slug || user.nom || '').toLowerCase().replace(/ /g, '_') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Desktop Navigation -->
        <nav class="desktop-nav">
          <div class="user-menu-wrapper" v-if="authStore.isAuthenticated">
            <div class="profile-link-container" @click="toggleUserMenu" :class="{ 'active': showUserMenu }">
              <div class="avatar-with-name">
                <img :src="profileImageUrl" :alt="`Photo de profil de ${authStore.user?.nom || 'utilisateur'}`" class="mini-avatar" @error="handleImgError" />
                <span class="user-name-header">{{ displayName }}</span>
                <span class="material-symbols-rounded dropdown-arrow">expand_more</span>
              </div>
            </div>

            <Transition name="fade-slide">
              <div v-if="showUserMenu" class="user-dropdown card shadow-lg">
                <div class="dropdown-user-info">
                  <img :src="profileImageUrl" :alt="`Photo de profil de ${authStore.user?.nom || 'utilisateur'}`" class="dropdown-avatar" @error="handleImgError" />
                  <div class="user-details">
                    <span class="full-name" @click="goToProfile">{{ displayName }}</span>
                    <span class="view-profile">Bienvenue 😊</span>
                  </div>
                  <div class="theme-toggle desktop-only" @click="themeStore.toggleTheme">
                    <span class="material-symbols-rounded">{{ themeStore.isDark ? 'light_mode' : 'dark_mode' }}</span>
                  </div>
                </div>
                
                <div class="dropdown-divider"></div>

                <!-- Admin Options -->
                <template v-if="authStore.user.role === 'admin'">
                  <div v-if="route.name !== 'admin-dashboard'" class="dropdown-item" @click="navigateTo('/admin/dashboard')">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">dashboard</span>
                    </div>
                    <span>Dashboard</span>
                  </div>
                  <div v-if="route.name !== 'admin-feedbacks'" class="dropdown-item" @click="navigateTo('/admin/feedbacks')">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">feedback</span>
                    </div>
                    <span>Feedbacks</span>
                  </div>
                  <div v-if="route.name !== 'admin-activities'" class="dropdown-item" @click="navigateTo('/admin/activites')">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">history</span>
                    </div>
                    <span>Activités</span>
                  </div>
                  <div v-if="route.name !== 'admin-badges'" class="dropdown-item" @click="navigateTo('/admin/badges')">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">stars</span>
                    </div>
                    <span>Badge</span>
                  </div>
                  <div v-if="route.name !== 'admin-reports'" class="dropdown-item" @click="navigateTo('/admin/signalements')">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">signal</span>
                    </div>
                    <span>Signalements</span>
                  </div>
                </template>

                <!-- User Options -->
                <template v-else>
                  <div v-if="route.name !== 'home'" class="dropdown-item" @click="goHome">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">home</span>
                    </div>
                    <span>Accueil</span>
                  </div>
                  <div v-if="route.name !== 'profile' && route.params.target_name === undefined" class="dropdown-item" @click="goToProfile">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">person</span>
                    </div>
                    <span>Mon Profil</span>
                  </div>
                  <div v-if="route.name !== 'add-post'" class="dropdown-item" @click="navigateTo({ name: 'add-post', params: { nom_user: userSlug || 'user' } })">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">add_circle</span>
                    </div>
                    <span>Ajouter un post</span>
                  </div>
                  <div class="dropdown-item" @click="navigateTo({ name: 'feedback', params: { nom_user: userSlug || 'user' } })">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">info</span>
                    </div>
                    <span>Envoyer un feedback</span>
                  </div>
                </template>

                <div class="dropdown-divider"></div>

                <div class="dropdown-item logout-item" @click="$emit('logout')">
                  <div class="item-icon-bg logout-icon">
                    <span class="material-symbols-rounded">logout</span>
                  </div>
                  <span>Déconnexion</span>
                </div>
              </div>
            </Transition>
          </div>
        </nav>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { useRouter, useRoute } from 'vue-router';
import api, { BASE_URL } from '@/utils/api';
import Loader from '@/components/Loader.vue';

const props = defineProps({
  unreadCount: { type: Number, default: 0 },
  isBlockedPage: { type: Boolean, default: false },
  isFullWidthPage: { type: Boolean, default: false }
});

const emit = defineEmits(['toggle-notifs', 'toggle-mobile-menu', 'logout']);

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

const showUserMenu = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchOverlay = ref(false);
const showMobileSearch = ref(false);
const searching = ref(false);
const isHashtagSearch = ref(false);
let searchTimeout = null;

const userSlug = computed(() => {
  const user = authStore.user?.data || authStore.user;
  const name = user?.slug || user?.nom || '';
  return name.replace(/ /g, '_');
});
const displayName = computed(() => {
  const user = authStore.user?.data || authStore.user;
  const name = user?.nom || 'User';
  return user?.role === 'admin' && name.length > 5 ? name.substring(0, 5) : name;
});

const profileImageUrl = computed(() => {
  const user = authStore.user?.data || authStore.user;
  if (user?.photo_profil) {
    if (user.photo_profil.startsWith('http')) return user.photo_profil;
    return `${BASE_URL}/storage/${user.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + (user?.nom || 'User');
});

const getUserAvatar = (user) => {
  if (user.photo_profil) {
    if (user.photo_profil.startsWith('http')) return user.photo_profil;
    return `${BASE_URL}/storage/${user.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + user.nom;
};

const handleImgError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=' + (authStore.user?.nom || 'User');
};

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  
  if (searchQuery.value.trim() === '') {
    searchResults.value = [];
    isHashtagSearch.value = false;
    return;
  }
  
  // Check if it's a hashtag search
  if (searchQuery.value.trim().startsWith('#')) {
    isHashtagSearch.value = true;
    searching.value = false;
    searchResults.value = [];
    if (!showMobileSearch.value) {
      showSearchOverlay.value = true;
    }
    return;
  }
  
  isHashtagSearch.value = false;
  searching.value = true;
  if (!showMobileSearch.value) {
    showSearchOverlay.value = true;
  }

  searchTimeout = setTimeout(async () => {
    try {
      const res = await api.get(`/users/search?query=${encodeURIComponent(searchQuery.value)}`);
      const users = res.data.data;
      searchResults.value = users.filter(user => user.role !== 'admin');
    } catch (err) {
      console.error('Search error', err);
    } finally {
      searching.value = false;
    }
  }, 300);
};

const clearSearch = () => {
  searchQuery.value = '';
  searchResults.value = [];
  showSearchOverlay.value = false;
  isHashtagSearch.value = false;
};

const navigateToHashtag = () => {
  const tag = searchQuery.value.replace('#', '').trim();
  if (tag) {
    showSearchOverlay.value = false;
    showMobileSearch.value = false;
    router.push({ 
      name: 'home', 
      params: { nom_user: userSlug.value || 'user' },
      query: { tag } 
    });
    clearSearch();
  }
};

const closeMobileSearch = () => {
  showMobileSearch.value = false;
  clearSearch();
};

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const  goHome = () => {
    if (authStore.user?.role === 'admin') {
        router.push('/admin/dashboard');
    } else {
        router.push({ name: 'home', params: { nom_user: userSlug.value || 'user' } });
    }
    showUserMenu.value = false;
};

const goToProfile = () => {
  router.push({ name: 'profile', params: { nom_user: userSlug.value || 'user' } });
  showUserMenu.value = false;
};

const navigateToUserProfile = (user) => {
  showSearchOverlay.value = false;
  showMobileSearch.value = false;
  const targetSlug = (user.slug || user.nom).replace(/ /g, '_');
  router.push({ 
    name: 'profile', 
    params: { 
        nom_user: authStore.user?.nom || 'user',
        target_name: targetSlug 
    } 
  });
  clearSearch();
};

const navigateTo = (path) => {
  router.push(path);
  showUserMenu.value = false;
};
</script>

<style scoped>
/* Styles moved from App.vue */
.main-header {
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 200;
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0.75em 0.25em;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.logo.clickable {
  font-family: 'Irish Grover';
  cursor: pointer;
  color: var(--primary-color);
  font-weight: 900;
  font-size: 2.3rem;
  letter-spacing: -1.5px;
  user-select: none;
}

@media (max-width: 480px) {
  .logo.clickable {
    font-size: 1.8rem;
    letter-spacing: -1px;
  }
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 20px;
}

.search-container {
  position: relative;
  width: 300px;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--input-bg);
  border-radius: 50px;
  padding: 8px 15px;
  height: 40px;
  transition: background 0.2s;
  width: 100%;
}

.search-input-wrapper:focus-within {
  background: var(--secondary-color);
}

.search-icon {
  color: var(--text-muted);
  font-size: 20px;
  margin-right: 8px;
}

.search-input {
  border: none;
  background: transparent;
  outline: none;
  width: 100%;
  font-size: 0.95rem;
  color: var(--text-color);
  padding-right: 25px;
}

.clear-icon {
  position: absolute;
  right: 12px;
  color: var(--text-muted);
  font-size: 18px;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s;
}

.clear-icon:hover {
  background: rgba(0,0,0,0.1);
  color: var(--text-color);
}

.search-results-overlay {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  margin-top: 8px;
  border-radius: 8px;
}

.no-results {
  padding: 12px;
  text-align: center;
  color: var(--text-muted);
  font-size: 0.9rem;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.search-result-item:hover {
  background: var(--secondary-color);
}

.search-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
}

.search-username {
  font-weight: 500;
  color: var(--text-color);
  font-size: 0.95rem;
}

/* Hashtag Search Result Styles */
.hashtag-search-result {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  cursor: pointer;
  transition: background 0.2s;
  border-radius: 8px;
}

.hashtag-search-result:hover {
  background: var(--secondary-color);
}

.hashtag-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), #6366f1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.hashtag-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.hashtag-text {
  font-weight: 700;
  font-size: 1rem;
  color: var(--primary-color);
}

.hashtag-hint {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.desktop-nav {
  display: none;
  gap: 20px;
  align-items: center;
}

.mini-avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.notif-btn, .theme-toggle {
  display: flex;
  position: relative;
  cursor: pointer;
  color: var(--text-muted);
  transition: color 0.2s;
}

.notif-btn:hover, .theme-toggle:hover {
  color: var(--primary-color);
}

.notif-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 10px;
  height: 10px;
  background: var(--error);
  border: 2px solid var(--card-bg);
  border-radius: 50%;
}

.user-menu-wrapper {
  position: relative;
}

.profile-link-container {
  cursor: pointer;
}

.avatar-with-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-name-header {
  font-weight: 600;
  color: var(--text-color);
}

.dropdown-arrow {
  font-size: 20px;
  color: var(--text-muted);
  transition: transform 0.2s;
}

.profile-link-container.active .dropdown-arrow {
  transform: rotate(180deg);
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  min-width: 300px;
  z-index: 1000;
}

.dropdown-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.dropdown-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.full-name {
  font-weight: 600;
  color: var(--text-color);
  cursor: pointer;
}

.full-name:hover {
  text-decoration: underline;
}

.view-profile {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.dropdown-divider {
  height: 1px;
  background: var(--border-color);
  margin: 12px 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.dropdown-item:hover {
  background: var(--secondary-color);
}

.item-icon-bg {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--secondary-color);
  display: flex;
  align-items: center;
  justify-content: center;
}

.logout-item {
  color: var(--error);
}

.logout-icon {
  background: rgba(240, 40, 73, 0.1);
}

@media (min-width: 768px) {
  .desktop-nav {
    display: flex;
  }
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.2s;
}

.fade-slide-enter-from, .fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Mobile Search Overlay */
.mobile-search-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--bg-color);
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.mobile-search-header {
    height: 60px;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 0 15px;
    border-bottom: 1px solid var(--border-color);
    background: var(--card-bg);
}

.search-results-content {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.search-info {
    display: flex;
    flex-direction: column;
    margin-left: 10px;
}

.search-email {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.icon-btn {
    background: none;
    border: none;
    color: var(--text-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
}

@media (max-width: 768px) {
  .desktop-nav {
    display: none;
  }
}
</style>
