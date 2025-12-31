<template>
  <div class="admin-feedbacks">
    <h2 class="page-title">Feedbacks</h2>

    <div v-if="feedbacks.length === 0" class="empty-state">
        <p>Aucun feedback pour le moment.</p>
    </div>

    <div v-else class="feedback-grid">
      <div v-for="feedback in feedbacks" :key="feedback.id" class="feedback-card card">
        <div class="feedback-header">
            <div class="user-row">
                <img :src="getAvatar(feedback.user)" class="avatar-xs" />
                <span class="user-name">{{ feedback.user.nom }}</span>
            </div>
            <div class="rating">
                <span v-for="n in 5" :key="n" class="material-symbols-rounded star" :class="{ filled: n <= feedback.rating }">star</span>
            </div>
        </div>
        <p class="feedback-text">"{{ feedback.comment }}"</p>
        <div class="feedback-footer">
            <span class="date">{{ new Date(feedback.created_at).toLocaleDateString() }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';

const feedbacks = ref([]);

const fetchFeedbacks = async () => {
    try {
        const res = await api.get('/admin/feedbacks');
        feedbacks.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

const getAvatar = (user) => {
    if (!user.photo_profil) return `https://ui-avatars.com/api/?name=${user.nom}`;
    return user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`;
};

onMounted(fetchFeedbacks);
</script>

<style scoped>
.feedback-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.feedback-card {
    padding: 20px;
    border-radius: 12px;
}

.feedback-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.user-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar-xs {
    width: 30px;
    height: 30px;
    border-radius: 50%;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.star {
    font-size: 18px;
    color: #e5e7eb;
}

.star.filled {
    color: #f59e0b;
    font-variation-settings: 'FILL' 1;
}

.feedback-text {
    color: #4b5563;
    font-style: italic;
    margin-bottom: 15px;
    line-height: 1.5;
}

.feedback-footer {
    text-align: right;
    font-size: 0.75rem;
    color: var(--text-muted);
}
</style>
