<template>
  <div class="admin-activity-view">
    <div class="admin-header">
        <div class="header-title">
            <span class="material-symbols-rounded">history</span>
            <h2>Activités Récentes</h2>
        </div>
        <p class="header-subtitle">Suivi de toutes les interactions sur la plateforme</p>
    </div>

    <div v-if="loading" class="loader-container">
        <Loader />
    </div>

    <div v-else class="activity-grid">
        <div v-for="activity in activities" :key="activity.id_activity" class="activity-card">
            <div class="card-header">
                <div class="user-row" @click="goToProfile(activity.user)">
                    <img :src="getAvatar(activity.user)" class="card-avatar" />
                    <div class="user-meta">
                        <span class="user-name">{{ activity.user.nom }}</span>
                        <span class="activity-date">le {{ formatDate(activity.created_at) }}</span>
                    </div>
                </div>
                <div class="action-icon-bg" :class="activity.action">
                    <span class="material-symbols-rounded">{{ formatActionIcon(activity.action) }}</span>
                </div>
            </div>

            <div class="card-body">
                <div class="action-row">
                    <span class="action-label">{{ formatAction(activity.action) }}</span>
                </div>
                <p class="activity-details">{{ activity.details }}</p>
            </div>
        </div>

        <div v-if="activities.length === 0" class="empty-state">
            <span class="material-symbols-rounded empty-icon">history</span>
            <p>Aucune activité enregistrée.</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import Loader from '@/components/Loader.vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const activities = ref([]);
const loading = ref(true);
const router = useRouter();
const authStore = useAuthStore();

const fetchActivities = async () => {
    try {
        const res = await api.get('/admin/activities');
        activities.value = res.data.data;
    } catch (err) {
        console.error('Fetch activities error', err);
    } finally {
        loading.value = false;
    }
};

const formatAction = (action) => {
    const actions = {
        'post': 'Publication',
        'like': 'Mention J\'aime',
        'commentaire': 'Commentaire',
        'follow': 'Abonnement',
        'follow_back': 'Abonnement en retour',
        'inscription': 'Inscription',
        'suppression_post': 'Suppression Post'
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
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getAvatar = (user) => {
    if (!user.photo_profil) return 'https://ui-avatars.com/api/?name=' + user.nom;
    return user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`;
};

const goToProfile = (user) => {
    router.push(`/${authStore.user.nom}/profil/${user.nom.replace(/ /g, '_')}`);
};

onMounted(fetchActivities);
</script>

<style scoped>
.admin-activity-view {
    padding: 20px 15px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

.admin-header {
    margin-bottom: 30px;
    text-align: left;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.header-title .material-symbols-rounded {
    font-size: 2.5rem;
    color: var(--primary-color);
}

.header-title h2 {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-color);
    margin: 0;
}

.header-subtitle {
    color: var(--text-muted);
    font-size: 1.1rem;
    font-weight: 500;
}

.activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.activity-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    border-color: var(--primary-color);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.user-row {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    flex: 1;
}

.card-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--secondary-color);
}

.user-meta {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 700;
    color: var(--text-color);
    font-size: 1rem;
    transition: color 0.2s;
}

.user-row:hover .user-name {
    color: var(--primary-color);
}

.activity-date {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.action-icon-bg {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-icon-bg .material-symbols-rounded {
    font-size: 20px;
}

/* Action Specific Colors */
.action-icon-bg.post { background: rgba(var(--primary-rgb, 24, 119, 242), 0.1); color: var(--primary-color); }
.action-icon-bg.like { background: rgba(var(--error-rgb, 240, 40, 73), 0.1); color: var(--error); }
.action-icon-bg.commentaire { background: rgba(var(--success-rgb, 66, 183, 42), 0.1); color: var(--success); }
.action-icon-bg.follow, .action-icon-bg.follow_back { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.action-icon-bg.inscription { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.action-icon-bg.suppression_post { background: rgba(var(--error-rgb, 240, 40, 73), 0.15); color: var(--error); }

.card-body {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-row {
    display: flex;
    align-items: center;
}

.action-label {
    font-size: 0.85rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
}

.activity-details {
    font-size: 0.95rem;
    color: var(--text-color);
    line-height: 1.4;
}

.empty-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: var(--card-bg);
    border-radius: 20px;
    border: 2px dashed var(--border-color);
    color: var(--text-muted);
    gap: 15px;
}

.empty-icon {
    font-size: 4rem;
    opacity: 0.3;
}

@media (max-width: 640px) {
    .activity-grid {
        grid-template-columns: 1fr;
    }
    
    .header-title h2 {
        font-size: 1.5rem;
    }
}
</style>
