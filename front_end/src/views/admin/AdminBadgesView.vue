<template>
  <div class="admin-badges-view">
    <div class="view-header">
      <div class="header-content">
        <h2 class="page-title">Gamification & Badges</h2>
        <p class="subtitle">Gérez les récompenses et les critères d'attribution</p>
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
      <div v-for="badge in badges" :key="badge.id_badge" class="badge-card" :style="{ borderLeftColor: badge.color }">
        <div class="card-header">
          <div class="badge-info-row">
            <div class="badge-icon-wrapper" :style="{ backgroundColor: badge.color + '15', color: badge.color }">
               <span class="material-symbols-rounded">{{ isValidIcon(badge.icon) ? badge.icon : 'star' }}</span>
            </div>
            <div class="badge-meta">
              <h3 class="badge-name">{{ badge.name }}</h3>
              <div class="badge-criteria-pill" :class="badge.criteria_type || 'manual'">
                 {{ badge.criteria_type ? formatCriteria(badge) : 'Attribution manuelle' }}
              </div>
            </div>
          </div>
          <div class="card-actions">
            <button class="icon-btn edit" @click="openModal(badge)" title="Modifier">
              <span class="material-symbols-rounded">edit</span>
            </button>
            <button class="icon-btn delete" @click="deleteBadge(badge)" title="Supprimer">
              <span class="material-symbols-rounded">delete</span>
            </button>
          </div>
        </div>
        
        <div class="card-body">
          <p class="badge-desc">{{ badge.description || 'Aucune description fournie.' }}</p>
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
            <div class="form-scroll-area">
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
                  <label>Icône personnalisée</label>
                  <div class="input-with-preview">
                    <input type="text" v-model="form.icon" required placeholder="star" class="form-input" />
                    <div class="icon-preview" :style="{ color: form.color }">
                      <span class="material-symbols-rounded">{{ form.icon || 'star' }}</span>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label>Couleur</label>
                  <div class="color-picker-wrapper">
                      <input type="color" v-model="form.color" class="color-input" />
                      <input type="text" v-model="form.color" class="form-input text-upper" spellcheck="false" />
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Choisir parmi les icônes suggérées</label>
                <div class="icon-gallery">
                  <button 
                    v-for="icon in suggestedIcons" 
                    :key="icon"
                    type="button"
                    class="gallery-item"
                    :class="{ active: form.icon === icon }"
                    @click="form.icon = icon"
                    :title="icon"
                  >
                    <span class="material-symbols-rounded">{{ icon }}</span>
                  </button>
                </div>
                <a href="https://fonts.google.com/icons" target="_blank" class="help-link">Plus d'icônes sur Google Fonts</a>
              </div>

              <div class="divider">Automatisation</div>

              <div class="form-group">
                  <label>Type de Critère</label>
                  <select v-model="form.criteria_type" class="form-input select">
                      <option :value="null">Aucun (Attribution Manuelle)</option>
                      <option value="posts_count">Nombre de posts publiés</option>
                      <option value="likes_received_count">Nombre de likes reçus</option>
                  </select>
              </div>

              <div class="form-group" v-if="form.criteria_type">
                  <label>Valeur requise pour l'obtention</label>
                  <input type="number" v-model="form.criteria_value" min="1" class="form-input" />
              </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeModal">Annuler</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    {{ submitting ? 'Enregistrement...' : 'Enregistrer le badge' }}
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

const suggestedIcons = [
  'star', 'verified', 'workspace_premium', 'military_tech', 'emoji_events', 
  'diamond', 'auto_awesome', 'rocket_launch', 'bolt', 'local_fire_department',
  'favorite', 'thumb_up', 'chat', 'forum', 'history_edu', 'edit_note',
  'person_check', 'group', 'campaign', 'volunteer_activism'
];

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
        const res = await api.get('/admin/badges');
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
            await api.put(`/admin/badges/${form.value.id_badge}`, form.value);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Badge modifié',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            await api.post('/admin/badges', form.value);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Badge créé',
                showConfirmButton: false,
                timer: 1500
            });
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
        title: 'Supprimer ce badge ?',
        text: `Voulez-vous vraiment supprimer le badge "${badge.name}" ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/admin/badges/${badge.id_badge}`);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Badge supprimé',
                showConfirmButton: false,
                timer: 1500
            });
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
    return icon && icon.length > 0;
};

onMounted(() => {
    fetchBadges();
});
</script>

<style scoped>
.admin-badges-view {
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

.subtitle {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}



.btn:active {
    transform: translateY(0);
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
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    padding-bottom: 20px;
}

@media (max-width: 992px) {
    .badges-grid {
        grid-template-columns: 1fr;
    }
}

.badge-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 25px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    border-left: 5px solid var(--primary-color);
}



.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.badge-info-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.badge-icon-wrapper {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    flex-shrink: 0;
}

.badge-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.badge-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-color);
}

.badge-criteria-pill {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 8px;
    width: fit-content;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-criteria-pill.posts_count { background: rgba(24, 119, 242, 0.1); color: var(--primary-color); }
.badge-criteria-pill.likes_received_count { background: #fee2e2; color: #f02849; }
.badge-criteria-pill.manual { background: var(--secondary-color); color: var(--text-muted); }

.card-actions {
    display: flex;
    gap: 8px;
}

.icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: var(--input-bg);
    cursor: pointer;
    color: var(--text-muted);
    transition: all 0.2s ease;
}

.icon-btn:hover {
    background: var(--secondary-color);
    color: var(--text-color);
}

.icon-btn.delete:hover {
    background: #fee2e2;
    color: #dc2626;
}

.card-body {
    background: var(--input-bg);
    padding: 15px;
    border-radius: 16px;
    border: 1px solid var(--border-color);
    flex-grow: 1;
}

.badge-desc {
    color: var(--text-color);
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
    opacity: 0.9;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(8px);
}

.modal-card {
    background: var(--card-bg);
    width: 95%;
    max-width: 550px;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    overflow: hidden;
    animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid var(--border-color);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
}

.modal-header h3 {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--text-color);
}

.close-btn {
    background: var(--secondary-color);
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.close-btn:hover {
    background: var(--input-bg);
    color: var(--text-color);
}

.modal-form {
    padding: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.form-scroll-area {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Webkit Scrollbar */
.form-scroll-area::-webkit-scrollbar {
    width: 6px;
}
.form-scroll-area::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 10px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-color);
    margin-left: 2px;
}

.form-input {
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    padding: 12px 16px;
    border-radius: 14px;
    color: var(--text-color);
    font-family: inherit;
    font-size: 0.95rem;
    width: 100%;
    outline: none;
    transition: all 0.2s;
}

.form-input:focus {
    border-color: var(--primary-color);
    background: var(--card-bg);
    box-shadow: 0 0 0 4px rgba(24, 119, 242, 0.1);
}

.input-with-preview {
  display: flex;
  gap: 12px;
  align-items: center;
}

.icon-preview {
  width: 46px;
  height: 46px;
  background: var(--secondary-color);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  border: 1px solid var(--border-color);
  flex-shrink: 0;
}

.icon-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 10px;
    padding: 15px;
    background: var(--input-bg);
    border-radius: 16px;
    border: 1px solid var(--border-color);
}

.gallery-item {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid transparent;
    background: var(--card-bg);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-color);
    transition: all 0.2s;
}

.gallery-item:hover {
    background: var(--secondary-color);
}

.gallery-item.active {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    box-shadow: 0 4px 10px rgba(24, 119, 242, 0.3);
}

.gallery-item span {
    font-size: 22px;
}

.row-group {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 20px;
}

.color-picker-wrapper {
    display: flex;
    gap: 10px;
}

.color-input {
    width: 46px;
    height: 46px;
    padding: 0;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    overflow: hidden;
}

.text-upper { text-transform: uppercase; }

.divider {
    display: flex;
    align-items: center;
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 10px 0;
}

.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

.divider::before { margin-right: 20px; }
.divider::after { margin-left: 20px; }

.modal-footer {
    padding: 20px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid var(--border-color);
    background: var(--card-bg);
    flex-shrink: 0;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-muted);
    background: var(--input-bg);
    border-radius: 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    color: #d1d5db;
}

.loader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
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

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Mobile Optimizations */
@media (max-width: 640px) {
    .admin-badges-view {
        padding: 15px;
        margin: 0.5em;
        border-radius: 16px;
    }

    .view-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
    }

    .btn-primary {
        width: 100%;
        justify-content: center;
    }

    .badge-card {
        padding: 15px;
        border-radius: 16px;
    }

    .badge-icon-wrapper {
        width: 44px;
        height: 44px;
        font-size: 22px;
        border-radius: 10px;
    }

    .badge-name {
        font-size: 1rem;
    }

    .card-body {
        padding: 12px;
        border-radius: 12px;
    }

    .badge-desc {
        font-size: 0.85rem;
    }

    .row-group {
        grid-template-columns: 1fr;
    }

    .icon-gallery {
        grid-template-columns: repeat(5, 1fr);
        padding: 10px;
    }

    .modal-card {
        border-radius: 20px 20px 0 0;
        align-self: flex-end;
    }

    .modal-header, .form-scroll-area, .modal-footer {
        padding: 15px 20px;
    }
}
</style>
