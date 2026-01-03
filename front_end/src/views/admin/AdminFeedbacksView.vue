<template>
  <div class="admin-feedbacks-view">
    <div class="view-header">
      <div class="header-content">
        <h2 class="page-title">Feedbacks</h2>
      </div>

      <div class="filter-wrapper">
         <div class="select-container">
            <span class="material-symbols-rounded filter-icon">filter_list</span>
            <select v-model="selectedFilter" class="custom-select">
                <option value="newest">Plus récents</option>
                <option value="oldest">Plus anciens</option>
                <option value="highest">Meilleures notes</option>
                <option value="lowest">Moins bonnes notes</option>
            </select>
            <span class="material-symbols-rounded arrow-icon">expand_more</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loader-container">
        <div class="spinner"></div>
    </div>

    <div v-else-if="sortedFeedbacks.length === 0" class="empty-state">
        <span class="material-symbols-rounded empty-icon">chat_bubble_outline</span>
        <p>Aucun feedback pour le moment.</p>
    </div>

    <div v-else class="feedback-grid">
      <div v-for="feedback in sortedFeedbacks" :key="feedback.id" class="feedback-card">
        <div class="card-header">
            <div class="user-row">
                <img :src="getAvatar(feedback.user)" class="avatar-sm" />
                <div class="user-meta">
                    <span class="user-name">{{ feedback.user.nom }}</span>
                    <span class="feedback-date">Envoyé le {{ formatDate(feedback.created_at) }}</span>
                </div>
            </div>
            <div class="rating-badge" :class="getRatingClass(feedback.rating)">
                <span class="material-symbols-rounded star-icon">star</span>
                <span>{{ feedback.rating }}.0</span>
            </div>
        </div>
        
        <div class="card-body">
            <p class="feedback-text">"{{ feedback.comment }}"</p>
        </div>

        <div class="card-footer">
             <button class="btn-action" title="Marquer comme lu" @click="markAsRead(feedback)">
                <span class="material-symbols-rounded">check</span>
             </button>
             <button class="btn-action delete" title="Supprimer" @click="deleteFeedback(feedback)">
                <span class="material-symbols-rounded">delete</span>
             </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import Swal from 'sweetalert2';

const feedbacks = ref([]);
const loading = ref(true);
const selectedFilter = ref('newest');

const fetchFeedbacks = async () => {
    loading.value = true;
    try {
        const res = await api.get('/admin/feedbacks');
        feedbacks.value = res.data;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const sortedFeedbacks = computed(() => {
    let sorted = [...feedbacks.value];
    switch (selectedFilter.value) {
        case 'newest':
            return sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        case 'oldest':
            return sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        case 'highest':
            return sorted.sort((a, b) => b.rating - a.rating);
        case 'lowest':
            return sorted.sort((a, b) => a.rating - b.rating);
        default:
            return sorted;
    }
});

const getAvatar = (user) => {
    if (!user.photo_profil) return `https://ui-avatars.com/api/?name=${user.nom}`;
    return user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`;
};

const formatDate = (dateString) => { 
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getRatingClass = (rating) => {
    if (rating >= 4) return 'high';
    if (rating >= 3) return 'medium';
    return 'low';
};

const deleteFeedback = async (feedback) => {
    const result = await Swal.fire({
        title: 'Supprimer cet avis ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
        try {
            // await api.delete(`/admin/feedbacks/${feedback.id}`); // Assuming endpoint exists
            feedbacks.value = feedbacks.value.filter(f => f.id !== feedback.id);
            Swal.fire('Supprimé', 'Le feedback a été supprimé.', 'success');
        } catch (err) {
            console.error(err);
            Swal.fire('Erreur', 'Impossible de supprimer le feedback.', 'error');
        }
    }
};

const markAsRead = (feedback) => {
    // Implement read logic
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Marqué comme lu',
        showConfirmButton: false,
        timer: 1500
    });
};

onMounted(fetchFeedbacks);
</script>

<style scoped>
.admin-feedbacks-view {
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
    min-width: 180px;
}

.custom-select:hover {
    background-color: var(--secondary-color);
}

.custom-select:focus {
    outline: none;
    border-color: var(--primary-color);
    background-color: var(--card-bg);
}

.feedback-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px; /* Increased gap */
    padding-bottom: 30px;
}

@media (max-width: 768px) {
    .feedback-grid {
        grid-template-columns: 1fr;
    }
}

.feedback-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px; 
    padding: 30px; 
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    min-height: 250px; 
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center; /* Center alignment */
    margin-bottom: 25px; /* Increased margin */
}

.user-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar-sm {
    width: 55px; 
    height: 55px; 
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
}

.feedback-date {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.rating-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
}

.rating-badge.high { background: #fef3c7; color: #d97706; }
.rating-badge.medium { background: #e0f2fe; color: #0284c7; }
.rating-badge.low { background: #fee2e2; color: #dc2626; }

.star-icon { font-size: 16px; }

.card-body {
    background: var(--input-bg);
    padding: 15px;
    border-radius: 16px; 
    border: 1px solid var(--border-color);
    margin-bottom: 25px; 
    flex-grow: 1;
}

.feedback-text {
    color: var(--text-color);
    font-style: italic;
    font-size: 1.1rem; 
    line-height: 1.6;
}

.card-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    background: var(--input-bg);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-action:hover {
    background: #dbeafe;
    color: var(--primary-color);
}

.btn-action.delete:hover {
    background: #fee2e2;
    color: #dc2626;
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
</style>
