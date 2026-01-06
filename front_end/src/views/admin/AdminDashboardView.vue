<template>
  <div class="admin-dashboard-layout">
    <!-- Sidebar / Stats Section -->
    <aside class="dashboard-sidebar">
      <!-- Header Removed as requested -->

      <div v-if="loading" class="loader-container">
        <div class="spinner"></div>
      </div>

      <div v-else class="stats-column">
        <div class="stat-card">
          <div class="icon-wrapper blue">
            <span class="material-symbols-rounded">group</span>
          </div>
          <div class="stat-content">
            <h3>Utilisateurs</h3>
            <p class="stat-value">{{ stats.total_users }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="icon-wrapper green">
            <span class="material-symbols-rounded">article</span>
          </div>
          <div class="stat-content">
            <h3>Posts</h3>
            <p class="stat-value">{{ stats.total_posts }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="icon-wrapper red">
            <span class="material-symbols-rounded">favorite</span>
          </div>
          <div class="stat-content">
            <h3>Likes</h3>
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

        <!-- Real-time Clock Card -->
        <div class="stat-card clock-card">
          <div class="icon-wrapper orange">
            <span class="material-symbols-rounded">schedule</span>
          </div>
          <div class="stat-content">
            <h3>Heure</h3>
            <p class="stat-value">{{ currentTime }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="dashboard-main">
      <AdminUsersView />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from '@/utils/api';
import AdminUsersView from './AdminUsersView.vue';

const stats = ref({
    total_users: 0,
    total_posts: 0,
    total_likes: 0,
    total_comments: 0,
    unread_reports: 0,
    unread_new_users: 0
});
const loading = ref(true);
const currentTime = ref('');

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

let statsInterval = null;
let clockInterval = null;

onMounted(() => {
    fetchStats();
    // Refresh stats every 30 seconds
    statsInterval = setInterval(fetchStats, 30000);

    // Clock Logic
    currentTime.value = new Date().toLocaleTimeString('fr-FR');
    clockInterval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString('fr-FR');
    }, 1000);
});

onUnmounted(() => {
    if (statsInterval) clearInterval(statsInterval);
    if (clockInterval) clearInterval(clockInterval);
});

</script>

<style scoped>
.admin-dashboard-layout {
    display: flex;
    min-height: calc(100vh - 64px); /* Assuming navbar height */
    gap: 30px;
    padding: 20px;
}

.dashboard-sidebar {
    padding:  0.75em 0;
    width: 250px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-header h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    font-weight: 700;
    margin-bottom: 10px;
}

.stats-column {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-card {
    background: var(--card-bg);
    padding: 20px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid var(--border-color);
    transition: all 0.2s;
}

.stat-card:hover {
    opacity: 0.85;
}


.icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.icon-wrapper span {
    font-size: 24px;
    color: white;
}

.icon-wrapper.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.icon-wrapper.green { background: linear-gradient(135deg, #10b981, #059669); }
.icon-wrapper.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
.icon-wrapper.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.icon-wrapper.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

.stat-content {
    overflow: hidden;
}

.stat-content h3 {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 4px;
    font-weight: 600;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-color);
}

.dashboard-main {
    flex-grow: 1;
    min-width: 0; /* Prevents overflow issues */
}

.loader-container {
    display: flex;
    justify-content: center;
    padding: 50px;
}

/* Responsive */
@media (max-width: 1024px) {
    .admin-dashboard-layout {
        flex-direction: column;
    }
    
    .dashboard-sidebar {
        width: 100%;
        flex-direction: column;
        overflow-x: visible;
        padding-bottom: 0;
    }

    .sidebar-header {
        display: none;
    }

    .stats-column {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        width: 100%;
    }

    .stat-card {
        min-width: 0;
        padding: 12px;
        gap: 12px;
    }

    .icon-wrapper {
        width: 36px;
        height: 36px;
    }

    .icon-wrapper span {
        font-size: 20px;
    }
    
    .stat-value {
        font-size: 1.1rem;
    }
    
    .stat-content h3 {
        font-size: 0.75rem;
    }
}

@media (max-width: 768px) {
    .clock-card {
        display: none !important;
    }
}
</style>