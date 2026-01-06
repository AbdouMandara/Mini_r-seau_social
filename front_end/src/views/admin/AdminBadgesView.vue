<template>
  <div class="admin-badges-view container">
    <div class="header-section">
      <div class="title-wrapper">
        <h1 class="page-title">Gamification & Badges</h1>
        <p class="page-subtitle">Gérez les récompenses et les critères d'attribution</p>
      </div>
      <button class="btn btn-primary" @click="openModal()">
        <span class="material-symbols-rounded">add</span>
        Nouveau Badge
      </button>
    </div>

    <div v-if="loading" class="loader-container">
      <div class="spinner"></div>
    </div>

    <div v-else-if="badges.length === 0" class="empty-state">
      <span class="material-symbols-rounded empty-icon">stars</span>
      <h3>Aucun badge configuré</h3>
      <p>Commencez par créer votre premier badge pour motiver les utilisateurs.</p>
    </div>

    <div v-else class="badges-grid">
      <div v-for="badge in badges" :key="badge.id_badge" class="badge-card">
        <div class="badge-header" :style="{ backgroundColor: badge.color + '20' }">
          <div class="badge-icon" :style="{ color: badge.color }">
             <!-- Try to render icon if it's a valid material symbol name, else show default -->
             <span class="material-symbols-rounded">{{ isValidIcon(badge.icon) ? badge.icon : 'star' }}</span>
          </div>
          <div class="badge-actions">
            <button class="icon-btn edit" @click="openModal(badge)">
              <span class="material-symbols-rounded">edit</span>
            </button>
            <button class="icon-btn delete" @click="deleteBadge(badge)">
              <span class="material-symbols-rounded">delete</span>
            </button>
          </div>
        </div>
        
        <div class="badge-body">
          <h3 class="badge-name">{{ badge.name }}</h3>
          <p class="badge-desc">{{ badge.description }}</p>
          
          <div class="badge-criteria" v-if="badge.criteria_type">
            <span class="criteria-label">Condition:</span>
            <span class="criteria-value" :class="badge.criteria_type">
              {{ formatCriteria(badge) }}
            </span>
          </div>
          <div class="badge-criteria" v-else>
            <span class="criteria-label">Manuel</span>
            <span class="criteria-value manual">Attribution manuelle</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-card">
          <div class="modal-header">
            <h3>{{ isEditing ? 'Modifier le badge' : 'Nouveau badge' }}</h3>
            <button class="close-btn" @click="closeModal">
              <span class="material-symbols-rounded">close</span>
            </button>
          </div>
          
          <form @submit.prevent="saveBadge" class="modal-form">
            <div class="form-group">
              <label>Nom du badge</label>
              <input type="text" v-model="form.name" required placeholder="Ex: Expert" class="form-input" />
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea v-model="form.description" rows="2" placeholder="Ex: A publié 50 posts..." class="form-input"></textarea>
            </div>

            <div class="row-group">
              <div class="form-group">
                <label>Icône (Material Symbol)</label>
                <input type="text" v-model="form.icon" required placeholder="Ex: star, verified..." class="form-input" />
                <a href="https://fonts.google.com/icons" target="_blank" class="help-link">Voir les icônes</a>
              </div>
              <div class="form-group">
                <label>Couleur</label>
                <div class="color-picker-wrapper">
                    <input type="color" v-model="form.color" class="color-input" />
                    <input type="text" v-model="form.color" class="form-input" />
                </div>
              </div>
            </div>

            <div class="divider">Automatisaton</div>

            <div class="form-group">
                <label>Type de Critère</label>
                <select v-model="form.criteria_type" class="form-input select">
                    <option :value="null">Aucun (Manuel)</option>
                    <option value="posts_count">Nombre de posts publiés</option>
                    <option value="likes_received_count">Nombre de likes reçus</option>
                </select>
            </div>

            <div class="form-group" v-if="form.criteria_type">
                <label>Valeur requise</label>
                <input type="number" v-model="form.criteria_value" min="1" class="form-input" />
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeModal">Annuler</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/api';
import Swal from 'sweetalert2';

const badges = ref([]);
const loading = ref(true);
const showModal = ref(false);
const submitting = ref(false);
const isEditing = ref(false);

const form = ref({
    id_badge: null,
    name: '',
    description: '',
    icon: 'star',
    color: '#1877f2',
    criteria_type: null,
    criteria_value: 0
});

const resetForm = () => {
    form.value = {
        id_badge: null,
        name: '',
        description: '',
        icon: 'star',
        color: '#1877f2',
        criteria_type: null,
        criteria_value: 0
    };
};

const fetchBadges = async () => {
    loading.value = true;
    try {
        const res = await api.get('/badges');
        badges.value = res.data;
    } catch (err) {
        console.error(err);
        Swal.fire('Erreur', 'Impossible de charger les badges', 'error');
    } finally {
        loading.value = false;
    }
};

const openModal = (badge = null) => {
    if (badge) {
        isEditing.value = true;
        form.value = { ...badge };
    } else {
        isEditing.value = false;
        resetForm();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const saveBadge = async () => {
    submitting.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/badges/${form.value.id_badge}`, form.value);
            Swal.fire('Succès', 'Badge modifié avec succès', 'success');
        } else {
            await api.post('/badges', form.value);
            Swal.fire('Succès', 'Badge créé avec succès', 'success');
        }
        closeModal();
        fetchBadges();
    } catch (err) {
        console.error(err);
        Swal.fire('Erreur', 'Une erreur est survenue', 'error');
    } finally {
        submitting.value = false;
    }
};

const deleteBadge = async (badge) => {
    const result = await Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1877f2',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/badges/${badge.id_badge}`);
            Swal.fire('Supprimé', 'Le badge a été supprimé.', 'success');
            fetchBadges();
        } catch (err) {
            Swal.fire('Erreur', 'Impossible de supprimer le badge', 'error');
        }
    }
};

const formatCriteria = (badge) => {
    if (badge.criteria_type === 'posts_count') return `${badge.criteria_value} posts publiés`;
    if (badge.criteria_type === 'likes_received_count') return `${badge.criteria_value} likes reçus`;
    return 'Inconnu';
};

const isValidIcon = (icon) => {
    // Simple check, in reality would need a list of valid icons
    return icon && icon.length > 0;
};

onMounted(() => {
    fetchBadges();
});
</script>

<style scoped>
.admin-badges-view {
    padding-top: 20px;
    padding-bottom: 40px;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-color);
    margin-bottom: 5px;
}

.page-subtitle {
    color: var(--text-muted);
}

.btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: transform 0.2s, opacity 0.2s;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-secondary {
    background: var(--secondary-color);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.badge-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.badge-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}

.badge-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.badge-icon {
    width: 50px;
    height: 50px;
    background: var(--card-bg);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.badge-actions {
    display: flex;
    gap: 8px;
}

.icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    color: var(--text-color);
    transition: all 0.2s;
}

.icon-btn:hover {
    background: white;
    transform: scale(1.1);
}

.icon-btn.delete:hover {
    color: var(--error);
}

.badge-body {
    padding: 20px;
}

.badge-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 5px;
}

.badge-desc {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 15px;
    line-height: 1.4;
    min-height: 2.8em; /* 2 lines */
}

.badge-criteria {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--body-bg);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
}

.criteria-label {
    color: var(--text-muted);
}

.criteria-value {
    font-weight: 600;
    color: var(--primary-color);
}

.criteria-value.manual {
    color: var(--text-muted);
}

/* Modal */
.modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.modal-card {
    background: var(--card-bg);
    width: 100%;
    max-width: 500px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-color);
}

.close-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 1.2rem;
    padding: 4px;
    border-radius: 50%;
    transition: background 0.2s;
}

.close-btn:hover {
    background: var(--secondary-color);
}

.modal-form {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-color);
}

.form-input {
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    padding: 10px 14px;
    border-radius: 8px;
    color: var(--text-color);
    font-family: inherit;
    font-size: 0.95rem;
    width: 100%;
    outline: none;
    transition: border-color 0.2s;
}

.form-input:focus {
    border-color: var(--primary-color);
}

.row-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.color-picker-wrapper {
    display: flex;
    gap: 10px;
}

.color-input {
    width: 42px;
    height: 42px;
    padding: 0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.help-link {
    font-size: 0.8rem;
    color: var(--primary-color);
    text-decoration: none;
}

.help-link:hover {
    text-decoration: underline;
}

.divider {
    display: flex;
    align-items: center;
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 10px 0;
}

.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

.divider::before { margin-right: 15px; }
.divider::after { margin-left: 15px; }

.modal-footer {
    margin-top: 10px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 60px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--secondary-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
