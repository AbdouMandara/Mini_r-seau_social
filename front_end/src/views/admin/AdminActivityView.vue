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

    <div v-else class="activity-content card">
        <div class="activity-table-wrapper">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Détails</th>
                        <th>Date & Heure</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="activity in activities" :key="activity.id_activity">
                        <td>
                            <div class="user-cell" @click="goToProfile(activity.user)">
                                <img :src="getAvatar(activity.user)" class="cell-avatar" />
                                <span class="cell-name">{{ activity.user.nom }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="action-badge" :class="activity.action">
                                {{ formatAction(activity.action) }}
                            </span>
                        </td>
                        <td class="details-cell">{{ activity.details }}</td>
                        <td class="date-cell">{{ formatDate(activity.created_at) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="activities.length === 0" class="empty-state">
            <p>Aucune activité enregistrée.</p>
        </div>

        <!-- Pagination could be added here if needed -->
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
        'inscription': 'Inscription',
        'suppression_post': 'Suppression Post'
    };
    return actions[action] || action;
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
    padding: 20px 0 80px;
    max-width: 1000px;
    margin: 0 auto;
}

.admin-header {
    margin-bottom: 25px;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--primary-color);
}

.header-title h2 {
    font-size: 1.8rem;
    margin: 0;
}

.header-subtitle {
    color: var(--text-muted);
    margin-top: 5px;
}

.activity-content {
    padding: 0;
    overflow: hidden;
}

.activity-table-wrapper {
    overflow-x: auto;
}

.activity-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.activity-table th {
    background: var(--input-bg);
    padding: 15px 20px;
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 600;
}

.activity-table td {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.95rem;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.user-cell:hover .cell-name {
    color: var(--primary-color);
    text-decoration: underline;
}

.cell-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.cell-name {
    font-weight: 600;
    transition: color 0.2s;
}

.action-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--input-bg);
}

.action-badge.post { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.action-badge.like { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.action-badge.commentaire { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.action-badge.follow { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.action-badge.inscription { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.details-cell {
    color: var(--text-color);
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.date-cell {
    color: var(--text-muted);
    font-size: 0.85rem;
}

.empty-state {
    padding: 50px;
    text-align: center;
    color: var(--text-muted);
}

@media (max-width: 767px) {
    .activity-table th, .activity-table td {
        padding: 12px 15px;
    }
}
</style>
