<template>
  <div class="app-wrapper">
    <!-- Header Desktop/Mobile -->
    <header class="main-header" v-if="!isBlockedPage">
      <div class="header-content container">
        <h1 class="logo clickable" @click="goHome">!Pozterr</h1>
        
        <div v-if="authStore.isAuthenticated && authStore.user" class="header-actions">
          <!-- Notification Bell (Mobile & Desktop) -->
          <!-- Search Bar (Desktop) -->
          <div v-if="!authStore.user.is_admin" class="search-container desktop-only">
             <div class="search-input-wrapper">
                <span class="material-symbols-rounded search-icon">search</span>
                <input 
                  type="text" 
                  v-model="searchQuery" 
                  @input="handleSearch" 
                  @focus="showSearchOverlay = true"
                  class="search-input" 
                  placeholder="Rechercher un utilisateur..." 
                />
                <!-- Clear Button -->
                <span v-if="searchQuery.length > 1" class="material-symbols-rounded clear-icon" @click="clearSearch">close</span>
             </div>

             <!-- Search Overlay -->
             <Transition name="fade">
                <div v-if="showSearchOverlay && (searchQuery.trim() !== '' || searching)" class="search-results-overlay card shadow-lg">
                   <!-- Loader in Overlay -->
                   <div v-if="searching" class="loader-container">
                       <Loader />
                   </div>
                   <!-- Results -->
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
                         <img :src="user.photo_profil ? (user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`) : 'https://ui-avatars.com/api/?name=' + user.nom" class="search-avatar" />
                         <span class="search-username">{{ user.nom }}</span>
                      </div>
                   </div>
                </div>
             </Transition>
          </div>

          <!-- Notification Bell (Desktop) -->
          <div class="theme-toggle desktop-only" @click="themeStore.toggleTheme">
            <span class="material-symbols-rounded">{{ themeStore.isDark ? 'light_mode' : 'dark_mode' }}</span>
          </div>

          <div class="notif-btn desktop-only" @click="toggleNotifs">
            <span class="material-symbols-rounded">notifications</span>
            <span v-if="unreadCount > 0" class="notif-badge"></span>
          </div>

          <!-- Mobile Hamburger -->
          <div class="notif-btn mobile-only" @click="toggleMobileMenu">
             <span class="material-symbols-rounded">menu</span>
             <span v-if="unreadCount > 0" class="notif-badge"></span>
          </div>

          <!-- Desktop Navigation -->
          <nav class="desktop-nav">
            <div class="user-menu-wrapper" v-if="authStore.isAuthenticated">
              <div class="profile-link-container" @click="toggleUserMenu" :class="{ 'active': showUserMenu }">
                <div class="avatar-with-name">
                  <img :src="profileImageUrl" class="mini-avatar" @error="handleImgError" />
                  <span class="user-name-header">{{ authStore.user.is_admin ? (authStore.user.nom.length > 5 ? authStore.user.nom.substring(0, 5) : authStore.user.nom) : authStore.user.nom }}</span>
                  <span class="material-symbols-rounded dropdown-arrow">expand_more</span>
                </div>
              </div>

              <!-- User Dropdown Menu -->
              <Transition name="fade-slide">
                <div v-if="showUserMenu" class="user-dropdown card shadow-lg">
                  <!-- User Header inside Dropdown -->
                  <div class="dropdown-user-info" @click="router.push(`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/profil`); showUserMenu = false">
                    <img :src="profileImageUrl" class="dropdown-avatar" @error="handleImgError" />
                    <div class="user-details">
                      <span class="full-name">{{ authStore.user.is_admin ? (authStore.user.nom.length > 5 ? authStore.user.nom.substring(0, 5) : authStore.user.nom) : authStore.user.nom }}</span>
                      <span class="view-profile">Bienvenue 😊</span>
                    </div>
                  </div>
                  
                  <div class="dropdown-divider"></div>

                  <!-- Menu Options -->
                  <!-- Admin Options -->
                  <template v-if="authStore.user.is_admin">
                    <!-- Bouton qui mène à la route 'dashboard' -->
                    <div v-if="route.name !== 'admin-dashboard'" class="dropdown-item" @click="router.push('/admin/dashboard'); showUserMenu = false">
                        <div class="item-icon-bg">
                        <span class="material-symbols-rounded">dashboard</span>
                        </div>
                        <span>Dashboard</span>
                    </div>
                    <!-- Bouton qui mène à la route 'feedback' -->
                    <div v-if="route.name !== 'admin-feedbacks'"  class="dropdown-item" @click="router.push('/admin/feedbacks'); showUserMenu = false">
                        <div class="item-icon-bg">
                        <span class="material-symbols-rounded">feedback</span>
                        </div>
                        <span>Feedbacks</span>
                    </div>
                  </template>

                  <!-- User Options -->
                  <template v-else>
                    <div v-if="route.name !== 'home'" class="dropdown-item" @click="goHome(); showUserMenu = false">
                        <div class="item-icon-bg">
                        <span class="material-symbols-rounded">home</span>
                        </div>
                        <span>Accueil</span>
                    </div>

                    <div v-if="route.name !== 'profile' && route.params.target_name === undefined" class="dropdown-item" @click="router.push(`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/profil`); showUserMenu = false">
                        <div class="item-icon-bg">
                        <span class="material-symbols-rounded">person</span>
                        </div>
                        <span>Mon Profil</span>
                    </div>

                    <div v-if="route.name !== 'add-post'" class="dropdown-item" @click="router.push(`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/add_post`); showUserMenu = false">
                        <div class="item-icon-bg">
                        <span class="material-symbols-rounded">add_circle</span>
                        </div>
                        <span>Ajouter un post</span>
                    </div>
                    <!-- Feed back -->
                    <div class="dropdown-item" @click="router.push(`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/feedback`); showUserMenu = false">
                        <div class="item-icon-bg">
                        <span class="material-symbols-rounded">info</span>
                        </div>
                        <span>Envoyer un feedback</span>
                    </div>
                  </template>


                  <div class="dropdown-divider"></div>

                  <div class="dropdown-item logout-item" @click="handleLogout(); showUserMenu = false">
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

    <!-- Notifications Drawer (Mobile/Desktop) -->
    <Transition name="slide">
      <div v-if="showNotifs" class="notif-drawer card">
        <div class="drawer-header">
          <h3>Notifications</h3>
          <button class="close-btn" @click="showNotifs = false">
            <span class="material-symbols-rounded">close</span>
          </button>
        </div>
        <div class="drawer-body">
          <div v-if="notifications.length === 0" class="empty-notifs">
            <p>Aucune notification</p>
          </div>
          <div v-for="n in notifications" :key="n.id_notif" class="notif-item" :class="{ 'unread': !n.is_read }">
             <img :src="n.author.photo_profil ? `${BASE_URL}/storage/${n.author.photo_profil}` : 'https://ui-avatars.com/api/?name=' + n.author.nom" class="notif-avatar" />
             <div class="notif-content">
               <p v-if="n.type === 'follow'"><strong>{{ n.author.nom }}</strong> a commencé à vous suivre</p>
               <p v-else-if="n.type === 'follow_back'"><strong>{{ n.author.nom }}</strong> vous a suivi en retour</p>
               <p v-else><strong>{{ n.author.nom }}</strong> a {{ n.type === 'like' ? 'liké' : 'commenté' }} votre post</p>
               <span v-if="n.post" class="post-preview">"{{ n.post.description.substring(0, 30) }}..."</span>
             </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Mobile Menu Drawer -->
    <Transition name="slide">
        <div v-if="showMobileMenu" class="notif-drawer card mobile-menu-content">
            <div class="drawer-header">
                <h3>Menu</h3>
                <button class="close-btn" @click="showMobileMenu = false">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="drawer-body">
                <div class="menu-item-mobile" @click="openNotifsFromMobile">
                    <div class="label-with-icon">
                        <span class="material-symbols-rounded">notifications</span>
                        Notifications
                    </div>
                    <span v-if="unreadCount > 0" class="badge-count">{{ unreadCount }}</span>
                </div>
                
                <!-- Admin Menu Items -->
                <template v-if="authStore.user.is_admin">
                    <div v-if="route.name !== 'admin-dashboard'" class="menu-item-mobile" @click="router.push('/admin/dashboard'); showMobileMenu = false">
                        <div class="label-with-icon">
                            <span class="material-symbols-rounded">dashboard</span>
                            Dashboard
                        </div>
                    </div>
                    <div v-if="route.name !== 'admin-feedbacks'" class="menu-item-mobile" @click="router.push('/admin/feedbacks'); showMobileMenu = false">
                        <div class="label-with-icon">
                            <span class="material-symbols-rounded">feedback</span>
                            Feedbacks
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

                <div class="menu-item-mobile logout-item-mobile" @click="handleLogout(); showMobileMenu = false">
                    <div class="label-with-icon">
                        <span class="material-symbols-rounded">logout</span>
                        Déconnexion
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Main Content -->
    <main class="main-container container">
      <router-view />
    </main>

    <!-- Footer Mobile -->
    <nav v-if="authStore.isAuthenticated && authStore.user && !route.path.startsWith('/admin') && !isBlockedPage" class="mobile-footer">
      <div class="footer-grid">
        <router-link :to="`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/home`" class="footer-item">
          <span class="material-symbols-rounded">home</span>
        </router-link>
        <router-link :to="`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/add_post`" class="footer-item">
          <div class="plus-btn">
            <span class="material-symbols-rounded">add</span>
          </div>
        </router-link>
        <router-link :to="`/${(authStore.user.slug || authStore.user.nom).replace(/ /g, '_')}/profil`" class="footer-item">
          <div class="mini-avatar-container">
            <img :src="profileImageUrl" class="mini-avatar" @error="handleImgError" />
          </div>
        </router-link>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { useRouter, useRoute } from 'vue-router';
import api, { BASE_URL } from '@/utils/api';
import Loader from '@/components/Loader.vue';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

const showNotifs = ref(false);
const showUserMenu = ref(false);
const notifications = ref([]);
const unreadCount = computed(() => notifications.value.filter(n => !n.is_read).length);
const prevUnreadCount = ref(0);

const isBlockedPage = computed(() => route.name === 'blocked');

const profileImageUrl = computed(() => {
  if (authStore.user?.photo_profil) {
    if (authStore.user.photo_profil.startsWith('http')) return authStore.user.photo_profil;
    return `${BASE_URL}/storage/${authStore.user.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + (authStore.user?.nom || 'User');
});

const showMobileMenu = ref(false);

// Search State
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchOverlay = ref(false);
const searching = ref(false);
let searchTimeout = null;

const handleSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    
    if (searchQuery.value.trim() === '') {
        searchResults.value = [];
        return;
    }
    
    // Only search if length > 1 (optional optimization, but user asked for clear button if > 1)
    // But for searching, usually we want to search even on 1 char if needed, or maybe 2.
    // User didn't restrict search length, just clear button visibility.
    
    searching.value = true;
    showSearchOverlay.value = true;

    searchTimeout = setTimeout(async () => {
        try {
            const res = await api.get(`/users/search?query=${encodeURIComponent(searchQuery.value)}`);
            searchResults.value = res.data;
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
};

const navigateToUserProfile = (user) => {
    const currentSlug = (authStore.user.slug || authStore.user.nom).replace(/ /g, '_');
    const targetSlug = (user.slug || user.nom).replace(/ /g, '_');
    
    router.push(`/${currentSlug}/profil/${targetSlug}`);
    
    showSearchOverlay.value = false;
    searchQuery.value = ''; 
    searchResults.value = [];
};

// Close search overlay when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            showSearchOverlay.value = false;
        }
    });
});

const handleImgError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=' + (authStore.user?.nom || 'User');
};

// Watch for user changes to refresh notifications immediately
watch(() => authStore.user, (val) => {
    if (val) {
        fetchNotifications();
    } else {
        notifications.value = [];
        unreadCount.value = 0; // Reset count
    }
});

const goHome = () => {
  if (authStore.isAuthenticated) {
    const username = (authStore.user.slug || authStore.user.nom).replace(/ /g, '_');
    router.push(`/${username}/home`);
  } else {
    router.push('/login');
  }
};

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
        background: '#fff',
        color: '#1c1e21'
    });

    if (result.isConfirmed) {
        await authStore.logout();
        router.push('/login');
        Swal.fire({
            title: 'Déconnecté',
            text: 'À bientôt sur !Pozterr !',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }
};

const fetchNotifications = async () => {
    if (!authStore.isAuthenticated) return;
    try {
        const res = await api.get('/notifications');
        const newNotifs = res.data;
        const newUnread = newNotifs.filter(n => !n.is_read).length;

        if (newUnread > prevUnreadCount.value) {
            const latest = newNotifs.find(n => !n.is_read);
            if (latest) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: latest.type === 'follow' ? `${latest.author.nom} a commencé à vous suivre` : 
                           latest.type === 'follow_back' ? `${latest.author.nom} vous a suivi en retour` :
                           `${latest.author.nom} a interagi avec votre post`,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#fff',
                    color: '#1c1e21'
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
    showUserMenu.value = false; // Close user menu if opening notifs
    showNotifs.value = !showNotifs.value;
    if (showNotifs.value && unreadCount.value > 0) {
        try {
            await api.post('/notifications/read');
            // Optimistic update
            notifications.value.forEach(n => n.is_read = true);
        } catch (err) {
            console.error('Mark as read error', err);
        }
    }
};

const toggleUserMenu = () => {
    showNotifs.value = false; // Close notifs if opening user menu
    showUserMenu.value = !showUserMenu.value;
};

// Close menus when clicking outside
const toggleMobileMenu = () => {
    showNotifs.value = false;
    showMobileMenu.value = !showMobileMenu.value;
};

const openNotifsFromMobile = () => {
    showMobileMenu.value = false;
    showNotifs.value = true;
};

const navigateToFeedback = () => {
    showMobileMenu.value = false;
    const username = (authStore.user.slug || authStore.user.nom).replace(/ /g, '_');
    router.push(`/${username}/feedback`);
};

// Close menus when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-menu-wrapper') && !e.target.closest('.profile-link-container')) {
            showUserMenu.value = false;
        }
        if (!e.target.closest('.notif-btn') && !e.target.closest('.notif-drawer')) {
            // Only close if not clicking hamburger. Hamburger toggles mobile menu.
            if (!showMobileMenu.value) showNotifs.value = false;
        }
         // Be careful with overlapping clicks
    });

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

.main-header {
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 200;
  height: 60px;
  display: flex;
  align-items: center;
  padding : 0.75em;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.logo.clickable {
  font-family : 'Irish Grover';
  cursor: pointer;
  color: var(--primary-color);
  font-weight: 900;
  font-size: 2.3rem;
  letter-spacing: -1.5px;
  user-select: none;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}

/* Search Bar Styles */
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
    padding-right: 25px; /* Space for clear icon */
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

.loader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
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

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.desktop-nav {
  display: none;
  gap: 20px;
  align-items: center;
}
.cta-add {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 25px;
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.2);
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
.profile-link {
    display: flex;
    align-items: center;
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

.logout-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.logout-btn:hover {
    color: var(--error);
}

.btn-home-link {
    background: none;
    border: none;
    color: var(--primary-color);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.btn-home-link:hover {
    background: var(--secondary-color);
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
}

@media (min-width: 768px) {
  .desktop-nav {
    display: flex;
  }
  .mobile-footer {
    display: none !important;
  }
  .mobile-only {
    display: none !important;
  }
  .desktop-only {
    display: flex !important; /* Ensure flex if appropriate */
  }
}
@media (max-width: 767px) {
    .desktop-only {
        display: none !important;
    }
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

.footer-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  height: 100%;
}

.footer-item {
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: var(--text-muted);
  transition: color 0.2s;
}

.footer-item span.material-symbols-rounded {
  font-size: 28px;
}

.footer-item.router-link-active {
  color: var(--primary-color);
}

.plus-btn {
  background: var(--primary-color);
  color: white;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Notif Drawer */
.notif-drawer {
    position: fixed;
    top: 70px;
    right: 15px;
    width: 320px;
    max-height: 400px;
    z-index: 300;
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
}

@media (max-width: 767px) {
    .notif-drawer {
        width: calc(100% - 30px);
        left: 15px;
    }
}

.drawer-header {
    padding: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.drawer-body {
    padding: 15px;
    overflow-y: auto;
}

.empty-notifs {
    text-align: center;
    color: var(--text-muted);
    padding: 20px;
}

.close-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
}

.notif-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: background 0.2s;
}

.notif-item:hover {
    background: var(--secondary-color);
}

.notif-item.unread {
    background-color: rgba(24, 119, 242, 0.1);
}

.notif-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.notif-content p {
    font-size: 0.9rem;
    margin: 0;
}

.post-preview {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-style: italic;
}

/* User Dropdown */
.user-menu-wrapper {
  position: relative;
}

.profile-link-container {
  cursor: pointer;
  padding: 5px 12px;
  border-radius: 30px;
  transition: background 0.2s;
}

.profile-link-container:hover, .profile-link-container.active {
  background: var(--secondary-color);
}

.avatar-with-name {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name-header {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.95rem;
}

.dropdown-arrow {
  color: var(--text-muted);
  font-size: 20px;
  transition: transform 0.2s;
}

.profile-link-container.active .dropdown-arrow {
  transform: rotate(180deg);
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 260px;
  padding: 10px;
  z-index: 400;
  border-radius: 20px;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
}

.dropdown-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.dropdown-user-info:hover {
  background: var(--secondary-color);
}

.dropdown-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.full-name {
  font-weight: 700;
  color: var(--text-color);
  font-size: 1rem;
}

.view-profile {
  font-size: 0.8rem;
  color: var(--primary-color);
  font-weight: 600;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--text-color);
  font-weight: 600;
  font-size: 0.95rem;
}

.item-icon-bg {
  width: 36px;
  height: 36px;
  background: var(--input-bg);
  border-radius: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.item-icon-bg span {
  font-size: 20px;
  color: var(--text-color);
}

.dropdown-item:hover {
  background: var(--secondary-color);
}

.dropdown-item:hover .item-icon-bg {
  background: var(--secondary-color);
}

.dropdown-item:hover .item-icon-bg span {
  color: var(--primary-color);
}

.dropdown-divider {
  height: 1px;
  background: var(--border-color);
  margin: 10px 0;
}

.logout-item:hover {
  background: rgba(240, 40, 73, 0.1);
  color: var(--error);
}

.logout-item:hover .item-icon-bg span {
  color: var(--error);
}

.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from, .fade-slide-leave-to {
  opacity: 0;
  transform: translateY(15px) scale(0.95);
}

.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-enter-from, .slide-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

.menu-item-mobile {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
    cursor: pointer;
}

.menu-item-mobile:hover {
    background: var(--secondary-color);
}

.label-with-icon {
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge-count {
    background: var(--error);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}
</style>
