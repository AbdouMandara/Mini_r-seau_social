<template>
  <Transition name="fade">
    <div v-if="isOpen" class="modal-backdrop" @click.self="close">
      <div class="modal-card-report">
        <div class="modal-header">
          <div class="header-icon">
            <span class="material-symbols-rounded">flag</span>
          </div>
          <h3>Signaler ce contenu</h3>
          <button class="close-btn" @click="close">
            <span class="material-symbols-rounded">close</span>
          </button>
        </div>

        <div class="modal-body">
          <p class="desc">Aidez-nous à garder la communauté sûre. Pourquoi signalez-vous ceci ?</p>
          
          <div class="options-list">
             <label v-for="option in predefinedReasons" :key="option" class="radio-option">
                <input type="radio" v-model="selectedReason" :value="option" />
                <span class="radio-label">{{ option }}</span>
             </label>
             <label class="radio-option">
                <input type="radio" v-model="selectedReason" value="other" />
                <span class="radio-label">Autre</span>
             </label>
          </div>

          <textarea 
            v-if="selectedReason === 'other'"
            v-model="customReason" 
            placeholder="Précisez le problème..." 
            class="report-textarea"
            rows="3"
          ></textarea>

          <p v-if="error" class="error-msg">{{ error }}</p>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="close">Annuler</button>
          <button class="btn btn-danger" @click="submitReport" :disabled="loading">
            {{ loading ? 'Envoi...' : 'Signaler' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed } from 'vue';
import api from '@/utils/api';
import Swal from 'sweetalert2';

const props = defineProps({
  isOpen: Boolean,
  postId: String,
  userId: String, // Target user ID
  userName: String // Target name for context
});

const emit = defineEmits(['close', 'submitted']);

const loading = ref(false);
const error = ref('');
const selectedReason = ref('Contenu inapproprié');
const customReason = ref('');

const predefinedReasons = [
    'Contenu inapproprié',
    'Fausses informations',
    'Harcèlement',
    'Spam',
    'Discours haineux'
];

const finalReason = computed(() => {
    return selectedReason.value === 'other' ? customReason.value : selectedReason.value;
});

const close = () => {
    emit('close');
    selectedReason.value = 'Contenu inapproprié';
    customReason.value = '';
    error.value = '';
};

const submitReport = async () => {
    if (selectedReason.value === 'other' && !customReason.value.trim()) {
        error.value = 'Veuillez préciser la raison.';
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        await api.post('/reports', {
            id_post: props.postId,
            id_reported_user: props.userId,
            reason: finalReason.value
        });

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Signalement envoyé. Merci !',
            showConfirmButton: false,
            timer: 3000
        });

        emit('submitted');
        close();
    } catch (err) {
        console.error(err);
        if (err.response?.status === 409) {
            error.value = 'Vous avez déjà signalé cet élément.';
        } else {
            error.value = 'Une erreur est survenue.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    backdrop-filter: blur(4px);
}

.modal-card-report {
    background: var(--card-bg);
    width: 90%;
    max-width: 450px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border-color);
}

.header-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.1);
    color: var(--error);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-header h3 {
    flex: 1;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-color);
}

.close-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
}

.modal-body {
    padding: 20px;
}

.desc {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.options-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.radio-option:hover {
    background: var(--secondary-color);
}

.radio-label {
    color: var(--text-color);
    font-size: 0.95rem;
}

.report-textarea {
    width: 100%;
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 10px;
    color: var(--text-color);
    font-family: inherit;
    resize: none;
    outline: none;
}

.report-textarea:focus {
    border-color: var(--primary-color);
}

.error-msg {
    color: var(--error);
    font-size: 0.85rem;
    margin-top: 10px;
}

.modal-footer {
    padding: 15px 20px;
    background: var(--secondary-color);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}

.btn-secondary {
    background: transparent;
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-danger {
    background: var(--error);
    color: white;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
