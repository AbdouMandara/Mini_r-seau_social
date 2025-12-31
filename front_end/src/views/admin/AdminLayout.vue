<template>
  <div class="admin-layout">
    <header class="admin-header">
      <div class="logo-container" @click="router.push('/admin/dashboard')">
        <h1>Admin<span>!pozterr</span></h1>
      </div>
      <nav class="admin-nav">
        <router-link to="/admin/dashboard" class="nav-item">
            <span class="material-symbols-rounded">dashboard</span> Dashboard
        </router-link>
        <router-link to="/admin/users" class="nav-item">
            <span class="material-symbols-rounded">group</span> Utilisateurs
        </router-link>
        <router-link to="/admin/feedbacks" class="nav-item">
             <span class="material-symbols-rounded">feedback</span> Feedbacks
        </router-link>
      </nav>
      <div class="user-menu">
        <span>Admin</span>
        <button @click="logout" class="logout-btn">
             <span class="material-symbols-rounded">logout</span>
        </button>
      </div>
    </header>

    <main class="admin-content">
      <router-view></router-view>
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const logout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>

<style scoped>
.admin-layout {
  min-height: 100vh;
  background: #f8f9fa;
  display: flex;
  flex-direction: column;
}

.admin-header {
  background: #ffffff;
  padding: 15px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo-container h1 {
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 1.5rem;
  color: #1c1e21;
  cursor: pointer;
}

.logo-container span {
  color: var(--primary-color);
}

.admin-nav {
    display: flex;
    gap: 20px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--text-muted);
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.2s;
}

.nav-item:hover, .nav-item.router-link-active {
    background: var(--secondary-color);
    color: var(--primary-color);
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 15px;
}

.logout-btn {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 5px;
}

.logout-btn:hover {
  color: var(--error);
}

.admin-content {
  flex: 1;
  padding: 30px;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}
</style>
