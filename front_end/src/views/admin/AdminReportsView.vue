<!--  C'est pour les signalements qui ont été fait sur le posts des gens -->
<template>
  <div class="admin-reports-view">
    <div class="view-header">
      <div class="header-content">
        <h2 class="page-title">Signalements</h2>
      </div>

      <div class="filter-wrapper">
         <div class="select-container">
            <span class="material-symbols-rounded filter-icon">filter_list</span>
            <select v-model="selectedFilter" class="custom-select">
                <option value="pending">En attente</option>
                <option value="resolved">Résolus</option>
                <option value="ignored">Ignorés</option>
                <option value="all">Tous</option>
            </select>
            <span class="material-symbols-rounded arrow-icon">expand_more</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loader-container">
        <AppLoader />
    </div>

    <div v-else-if="filteredReports.length === 0" class="empty-state">
        <span class="material-symbols-rounded empty-icon">flag_off</span>
        <p>Aucun signalement {{ selectedFilter === 'all' ? '' : selectedFilter === 'pending' ? 'en attente' : selectedFilter === 'resolved' ? 'résolu' : 'ignoré' }}.</p>
    </div>

    <div v-else class="reports-grid">
      <div v-for="report in filteredReports" :key="report.id" class="report-card" :class="report.status">
        <div class="card-header">
            <div class="user-row">
                <img :src="getAvatar(report.reporter)" class="avatar-sm" />
                <div class="user-meta">
                    <span class="user-name">{{ report.reporter?.nom || 'Utilisateur inconnu' }}</span>
                    <span class="report-date">{{ formatDate(report.created_at) }}</span>
                </div>
            </div>
            <div class="status-badge" :class="report.status">
                <span>{{ getStatusLabel(report.status) }}</span>
            </div>
        </div>
        
        <div class="card-body">
            <div class="report-target">
                <span class="target-label">Post de </span>
                <span v-if="report.post" class="target-value"> {{ report.post.user?.nom }}</span>
                <span v-else-if="report.reported_user" class="target-value">Utilisateur {{ report.reported_user?.nom }}</span>
            </div>
            <p class="report-reason">"{{ report.reason }}"</p>
        </div>

        <div class="card-footer" v-if="report.status === 'pending'">
             <button class="btn-action resolve" title="Marquer comme résolu" @click="updateStatus(report, 'resolved')">
                <span class="material-symbols-rounded">check_circle</span>
             </button>
             <button class="btn-action ignore" title="Ignorer" @click="updateStatus(report, 'ignored')">
                <span class="material-symbols-rounded">cancel</span>
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
import AppLoader from "../../components/Loader.vue";

const reports = ref([]);
const loading = ref(true);
const selectedFilter = ref('pending');

const fetchReports = async () => {
    loading.value = true;
    try {
        const res = await api.get('/admin/reports');
        reports.value = res.data;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const filteredReports = computed(() => {
    if (selectedFilter.value === 'all') {
        return reports.value;
    }
    console.log(reports.value);
    
    return reports.value.filter(r => r.status === selectedFilter.value);
});

const getAvatar = (user) => {
    if (!user?.photo_profil) return `https://ui-avatars.com/api/?name=${user?.nom || 'U'}`;
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

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending': return 'En attente';
        case 'resolved': return 'Résolu';
        case 'ignored': return 'Ignoré';
        default: return status;
    }
};

const updateStatus = async (report, status) => {
    try {
        await api.put(`/admin/reports/${report.id}`, { status });
        report.status = status;
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: status === 'resolved' ? 'Signalement résolu' : 'Signalement ignoré',
            showConfirmButton: false,
            timer: 1500
        });
    } catch (err) {
        console.error(err);
        Swal.fire('Erreur', 'Impossible de mettre à jour le signalement.', 'error');
    }
};

onMounted(fetchReports);
</script>

<style scoped>
.admin-reports-view {
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

.reports-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    padding-bottom: 30px;
}

@media (max-width: 768px) {
    .reports-grid {
        grid-template-columns: 1fr;
    }
}

.report-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px; 
    padding: 30px; 
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    min-height: 220px;
    border-left: 4px solid var(--warning);
}

.report-card.resolved {
    border-left-color: var(--success);
    opacity: 0.7;
}

.report-card.ignored {
    border-left-color: var(--text-muted);
    opacity: 0.5;
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
}

.report-date {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.status-badge {
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
}

.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.resolved { background: #d1fae5; color: #059669; }
.status-badge.ignored { background: #e5e7eb; color: #6b7280; }

.card-body {
    background: var(--input-bg);
    padding: 15px;
    border-radius: 16px; 
    border: 1px solid var(--border-color);
    margin-bottom: 20px;
    flex-grow: 1;
}

.report-target {
    display: flex;
    gap: 6px;
    margin-bottom: 10px;
    font-size: 0.85rem;
}

.target-label {
    color: var(--text-muted);
}

.target-value {
    color: var(--primary-color);
    font-weight: 500;
}

.report-reason {
    color: var(--text-color);
    font-style: italic;
    font-size: 1rem;
    line-height: 1.5;
}

.card-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}

.btn-action {
    width: 40px;
    height: 40px;
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

.btn-action.resolve:hover {
    background: #d1fae5;
    color: #059669;
}

.btn-action.ignore:hover {
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
