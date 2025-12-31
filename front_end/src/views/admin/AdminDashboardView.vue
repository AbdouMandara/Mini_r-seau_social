<template>
  <div class="admin-dashboard">
    <h2 class="page-title">Bienvenue, Admin</h2>

    <div v-if="loading" class="loader-container">
        <div class="spinner"></div> <!-- Simple spinner or component -->
    </div>

    <div v-else class="stats-grid">
      <div class="stat-card">
        <div class="icon-wrapper blue">
            <span class="material-symbols-rounded">group</span>
        </div>
        <div class="stat-content">
            <h3>Utilisateurs Total</h3>
            <p class="stat-value">{{ stats.total_users }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon-wrapper green">
            <span class="material-symbols-rounded">wifi</span>
        </div>
        <div class="stat-content">
            <h3>En Ligne (5min)</h3>
            <p class="stat-value">{{ stats.online_users }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon-wrapper red">
            <span class="material-symbols-rounded">favorite</span>
        </div>
        <div class="stat-content">
            <h3>Likes Total</h3>
            <p class="stat-value">{{ stats.total_likes }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon-wrapper purple">
            <span class="material-symbols-rounded">chat_bubble</span>
        </div>
        <div class="stat-content">
            <h3>Commentaires</h3>
            <p class="stat-value">{{ stats.total_comments }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/api';

const stats = ref({
    total_users: 0,
    online_users: 0,
    total_likes: 0,
    total_comments: 0
});
const loading = ref(true);

const fetchStats = async () => {
    try {
        const res = await api.get('/admin/dashboard');
        stats.value = res.data;
    } catch (err) {
        console.error('Failed to fetch admin stats', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
    // Refresh stats every 30 seconds
    setInterval(fetchStats, 30000);
});
</script>

<style scoped>
.page-title {
    margin-bottom: 30px;
    font-size: 1.8rem;
    color: #1c1e21;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-wrapper span {
    font-size: 32px;
    color: white;
}

.icon-wrapper.blue { background: #3b82f6; }
.icon-wrapper.green { background: #10b981; }
.icon-wrapper.red { background: #ef4444; }
.icon-wrapper.purple { background: #8b5cf6; }

.stat-content h3 {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 5px;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1c1e21;
}

.loader-container {
    display: flex;
    justify-content: center;
    padding: 50px;
}
</style>
