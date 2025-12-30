<template>
  <div class="feedback-view">
    <div class="desktop-back desktop-only">
      <button class="btn-text back-link" @click="router.back()">
        <span class="material-symbols-rounded">arrow_back</span>
        Retour
      </button>
    </div>

    <div class="card">
      <h2 class="form-title">Donnez votre avis</h2>
      
      <form @submit.prevent="handleSubmit">
        <div class="input-group">
          <label>Votre nom</label>
          <input 
            type="text" 
            :value="authStore.user?.nom" 
            class="input-control readonly" 
            readonly
          >
        </div>

        <div class="input-group">
          <label>Votre note</label>
          <div class="rating-stars">
            <span 
              v-for="star in 5" 
              :key="star" 
              class="star" 
              :class="{ filled: star <= form.rating }"
              @click="form.rating = star"
            >
              ★
            </span>
          </div>
          <div class="rating-text" v-if="form.rating > 0">
            {{ form.rating }}/5
          </div>
        </div>

        <div class="input-group">
          <label>Commentaire (max 255 caractères)</label>
          <textarea 
            v-model="form.comment" 
            class="input-control text-area" 
            maxlength="255"
            placeholder="Dites-nous ce que vous pensez..."
            required
          ></textarea>
          <div class="char-count" :class="{ 'warning': form.comment.length >= 240 }">
            {{ form.comment.length }} / 255
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading || form.rating === 0">
          {{ loading ? 'Envoi...' : 'Envoyer mon feedback' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/api';
import Swal from 'sweetalert2';

const router = useRouter();
const authStore = useAuthStore();
const loading = ref(false);

const form = reactive({
  rating: 0,
  comment: ''
});

const handleSubmit = async () => {
  if (form.rating === 0) {
    Swal.fire('Oups', 'Veuillez donner une note', 'warning');
    return;
  }

  loading.value = true;
  try {
    await api.post('/feedback', form);
    
    Swal.fire({
      title: 'Merci !',
      text: 'Nous avons bien reçu votre feedback.',
      icon: 'success',
      confirmButtonText: 'Super',
      confirmButtonColor: '#1877f2'
    });

    // Reset or redirect
    form.rating = 0;
    form.comment = '';
    setTimeout(() => router.back(), 2000);

  } catch (err) {
    console.error('Feedback error', err);
    Swal.fire('Erreur', 'Une erreur est survenue lors de l\'envoi', 'error');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.feedback-view {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px 15px 100px;
}

.desktop-back { margin-bottom: 20px; }

.back-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-weight: 600;
    border: none;
    background: none;
    cursor: pointer;
}

.form-title {
    margin-bottom: 25px;
    font-size: 1.5rem;
    color: #1c1e21;
}

.input-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #dddfe2;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.input-control.readonly {
    background-color: #f0f2f5;
    color: var(--text-muted);
    cursor: not-allowed;
}

.rating-stars {
    display: flex;
    gap: 10px;
    font-size: 2rem;
    cursor: pointer;
    color: #dddfe2;
}

.star.filled {
    color: #ffc107;
}

.rating-text {
    margin-top: 5px;
    font-weight: bold;
    color: #ffc107;
}

.text-area {
  min-height: 120px;
  resize: none;
  font-family: inherit;
}

.char-count {
  text-align: right;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 4px;
}

.char-count.warning { color: var(--error); }

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    font-weight: bold;
    padding: 12px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    width: 100%;
    margin-top: 20px;
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@media (max-width: 767px) {
    .desktop-only { display: none; }
}
</style>