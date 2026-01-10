<template>
  <div class="admin-activity-view">
    <div class="view-header">
      <div class="header-content">
        <h2 class="page-title">Activités Récentes</h2>
      </div>

      <div class="filter-wrapper">
         <div class="select-container">
            <span class="material-symbols-rounded filter-icon">filter_list</span>
            <select v-model="selectedFilter" class="custom-select">
                <option value="all">Toutes</option>
                <option value="post">Publications</option>
                <option value="like">Likes</option>
                <option value="commentaire">Commentaires</option>
                <option value="follow">Abonnements</option>
                <option value="inscription">Inscriptions</option>
            </select>
            <span class="material-symbols-rounded arrow-icon">expand_more</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loader-container">
        <div class="spinner"></div>
    </div>

    <div v-else-if="filteredActivities.length === 0" class="empty-state">
        <span class="material-symbols-rounded empty-icon">history</span>
        <p>Aucune activité {{ selectedFilter !== 'all' ? 'de ce type' : '' }} enregistrée.</p>
    </div>

    <div v-else class="activity-grid">
      <div v-for="activity in filteredActivities" :key="activity.id_activity" class="activity-card" :class="activity.action">
        <div class="card-header">
            <div class="user-row">
                <img :src="getAvatar(activity.user)" class="avatar-sm" />
                <div class="user-meta">
                    <span class="user-name">{{ activity.user?.nom || 'Utilisateur' }}</span>
                    <span class="activity-date">{{ formatDate(activity.created_at) }}</span>
                </div>
            </div>
            <div class="action-badge" :class="activity.action">
                <span class="material-symbols-rounded">{{ formatActionIcon(activity.action) }}</span>
                <span>{{ formatAction(activity.action) }}</span>
            </div>
        </div>
        
        <div class="card-body">
            <p class="activity-details">"{{ activity.details }}"</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const activities = ref([]);
const loading = ref(true);
const selectedFilter = ref('all');
const router = useRouter();
const authStore = useAuthStore();

const fetchActivities = async () => {
    loading.value = true;
    try {
        const res = await api.get('/admin/activities');
        activities.value = res.data.data || res.data;
    } catch (err) {
        console.error('Fetch activities error', err);
    } finally {
        loading.value = false;
    }
};

const filteredActivities = computed(() => {
    if (selectedFilter.value === 'all') {
        return activities.value;
    }
    return activities.value.filter(a => a.action === selectedFilter.value);
});

const formatAction = (action) => {
    const actions = {
        'post': 'Publication',
        'like': 'Like',
        'commentaire': 'Commentaire',
        'follow': 'Abonnement',
        'follow_back': 'Follow back',
        'inscription': 'Inscription',
        'suppression_post': 'Suppression'
    };
    return actions[action] || action;
};

const formatActionIcon = (action) => {
    const icons = {
        'post': 'article',
        'like': 'favorite',
        'commentaire': 'chat_bubble',
        'follow': 'person_add',
        'follow_back': 'person_add',
        'inscription': 'login',
        'suppression_post': 'delete_forever'
    };
    return icons[action] || 'info';
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getAvatar = (user) => {
    if (!user?.photo_profil) return `https://ui-avatars.com/api/?name=${user?.nom || 'U'}`;
    return user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`;
};

onMounted(fetchActivities);
</script>

<style scoped>
.admin-activity-view {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 25px;
    border: 1px solid var(--border-color);
    height: 100%;
    margin: 0.75em;
}

.view-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.page-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-color);
    margin-bottom: 4px;
}

.filter-wrapper {
    display: flex;
    align-items: center;
}

.select-container {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 240px;
}

.filter-icon {
    position: absolute;
    left: 12px;
    color: var(--text-muted);
    font-size: 20px;
    pointer-events: none;
    z-index: 1;
}

.arrow-icon {
    position: absolute;
    right: 12px;
    color: var(--text-muted);
    font-size: 20px;
    pointer-events: none;
    z-index: 1;
}

.custom-select {
    appearance: none;
    -webkit-appearance: none;
    padding: 10px 40px 10px 40px;
    font-size: 0.9rem;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    background-color: var(--input-bg);
    color: var(--text-color);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
}

.custom-select:hover {
    background-color: var(--secondary-color);
}

.custom-select:focus {
    outline: none;
    border-color: var(--primary-color);
    background-color: var(--card-bg);
}

.activity-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    padding-bottom: 30px;
}

@media (max-width: 768px) {
    .activity-grid {
        grid-template-columns: 1fr;
    }
    
    .view-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}

.activity-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px; 
    padding: 30px; 
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    min-height: 180px;
    border-left: 4px solid var(--primary-color);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.user-row {
    display: flex;
    align-items: center;
    gap: 12px;
}


.avatar-sm {
    width: 50px; 
    height: 50px; 
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--card-bg);
}

.user-meta {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text-color);
    transition: color 0.2s;
}

.activity-date {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.action-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
}

.action-badge .material-symbols-rounded {
    font-size: 16px;
}

.action-badge.post { background: rgba(24, 119, 242, 0.1); color: var(--primary-color); }
.action-badge.like { background: #fee2e2; color: #f02849; }
.action-badge.commentaire { background: #d1fae5; color: #059669; }
.action-badge.follow, .action-badge.follow_back { background: #ede9fe; color: #8b5cf6; }
.action-badge.inscription { background: #fef3c7; color: #d97706; }
.action-badge.suppression_post { background: #fee2e2; color: #dc2626; }

.card-body {
    background: var(--input-bg);
    padding: 15px;
    border-radius: 16px; 
    border: 1px solid var(--border-color);
    flex-grow: 1;
}

.activity-details {
    color: var(--text-color);
    font-style: italic;
    font-size: 1rem;
    line-height: 1.5;
}

.empty-state {
    text-align: center;
    padding: 60px;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 10px;
    color: #d1d5db;
}

.loader-container {
    display: flex;
    justify-content: center;
    padding: 50px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--border-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
