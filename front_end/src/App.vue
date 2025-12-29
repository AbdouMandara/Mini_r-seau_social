<template>
  <div class="app-wrapper">
    <!-- Header Desktop/Mobile -->
    <header class="main-header">
      <div class="header-content container">
        <h1 class="logo clickable" @click="goHome">!Pozterr</h1>
        
        <div v-if="authStore.isAuthenticated" class="header-actions">
          <!-- Notification Bell (Mobile & Desktop) -->
          <div class="notif-btn" @click="toggleNotifs">
            <span class="material-symbols-rounded">notifications</span>
            <span v-if="unreadCount > 0" class="notif-badge"></span>
          </div>

          <!-- Desktop Navigation -->
          <nav class="desktop-nav">
            <div class="user-menu-wrapper" v-if="authStore.isAuthenticated">
              <div class="profile-link-container" @click="toggleUserMenu" :class="{ 'active': showUserMenu }">
                <div class="avatar-with-name">
                  <img :src="profileImageUrl" class="mini-avatar" @error="handleImgError" />
                  <span class="user-name-header">{{ authStore.user.nom }}</span>
                  <span class="material-symbols-rounded dropdown-arrow">expand_more</span>
                </div>
              </div>

              <!-- User Dropdown Menu -->
              <Transition name="fade-slide">
                <div v-if="showUserMenu" class="user-dropdown card shadow-lg">
                  <!-- User Header inside Dropdown -->
                  <div class="dropdown-user-info" @click="router.push(`/${authStore.user.nom}/profil`); showUserMenu = false">
                    <img :src="profileImageUrl" class="dropdown-avatar" @error="handleImgError" />
                    <div class="user-details">
                      <span class="full-name">{{ authStore.user.nom }}</span>
                      <span class="view-profile">Voir mon profil</span>
                    </div>
                  </div>
                  
                  <div class="dropdown-divider"></div>

                  <!-- Menu Options -->
                  <div v-if="route.name !== 'home'" class="dropdown-item" @click="goHome(); showUserMenu = false">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">home</span>
                    </div>
                    <span>Accueil</span>
                  </div>

                  <div v-if="route.name !== 'profile' && route.params.target_name === undefined" class="dropdown-item" @click="router.push(`/${authStore.user.nom}/profil`); showUserMenu = false">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">person</span>
                    </div>
                    <span>Mon Profil</span>
                  </div>

                  <div v-if="route.name !== 'add-post'" class="dropdown-item" @click="router.push(`/${authStore.user.nom}/add_post`); showUserMenu = false">
                    <div class="item-icon-bg">
                      <span class="material-symbols-rounded">add_circle</span>
                    </div>
                    <span>Ajouter un post</span>
                  </div>

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
               <p><strong>{{ n.author.nom }}</strong> a {{ n.type === 'like' ? 'liké' : 'commenté' }} votre post</p>
               <span class="post-preview">"{{ n.post.description.substring(0, 30) }}..."</span>
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
    <nav v-if="authStore.isAuthenticated" class="mobile-footer">
      <div class="footer-grid">
        <router-link :to="`/${authStore.user.nom}/home`" class="footer-item">
          <span class="material-symbols-rounded">home</span>
        </router-link>
        <router-link :to="`/${authStore.user.nom}/add_post`" class="footer-item">
          <div class="plus-btn">
            <span class="material-symbols-rounded">add</span>
          </div>
        </router-link>
        <router-link :to="`/${authStore.user.nom}/profil`" class="footer-item">
          <div class="mini-avatar-container">
            <img :src="profileImageUrl" class="mini-avatar" @error="handleImgError" />
          </div>
        </router-link>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import api, { BASE_URL } from '@/utils/api';
import Loader from '@/components/Loader.vue';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const showNotifs = ref(false);
const showUserMenu = ref(false);
const notifications = ref([]);
const unreadCount = computed(() => notifications.value.filter(n => !n.is_read).length);
const prevUnreadCount = ref(0);

const profileImageUrl = computed(() => {
  if (authStore.user?.photo_profil) {
    if (authStore.user.photo_profil.startsWith('http')) return authStore.user.photo_profil;
    return `${BASE_URL}/storage/${authStore.user.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + (authStore.user?.nom || 'User');
});

const handleImgError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=' + (authStore.user?.nom || 'User');
};

const goHome = () => {
  if (authStore.isAuthenticated) {
    router.push(`/${authStore.user.nom}/home`);
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
                    title: `${latest.author.nom} a interagi avec votre post`,
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
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-menu-wrapper') && !e.target.closest('.profile-link-container')) {
            showUserMenu.value = false;
        }
        if (!e.target.closest('.notif-btn') && !e.target.closest('.notif-drawer')) {
            showNotifs.value = false;
        }
    });

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
  background: var(--white);
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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

.mini-avatar:hover {
    transform: scale(1.05);
}

.profile-link {
    display: flex;
    align-items: center;
}

.notif-btn {
  display: flex;
  position: relative;
  cursor: pointer;
  color: var(--text-muted);
  transition: color 0.2s;
}

.notif-btn:hover {
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
    background: #f0f2f5;
}

.notif-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    background: var(--error);
    border: 2px solid white;
    border-radius: 50%;
}

.mobile-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--white);
  border-top: 1px solid #dddfe2;
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
    display: none;
  }
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
  box-shadow: 0 4px 15px rgba(24, 119, 242, 0.4);
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
    border-bottom: 1px solid #eee;
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
    border-bottom: 1px solid #f0f2f5;
    transition: background 0.2s;
}

.notif-item.unread {
    background-color: #f0f7ff;
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
  border: 1px solid #f0f2f5;
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
  background: #f0f2f5;
  border-radius: 50%;
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
  background: white;
}

.dropdown-item:hover .item-icon-bg span {
  color: var(--primary-color);
}

.dropdown-divider {
  height: 1px;
  background: #f0f2f5;
  margin: 10px 0;
}

.logout-item:hover {
  background: #fff0f0;
  color: var(--error);
}

.logout-item:hover .item-icon-bg {
  background: white;
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
</style>
