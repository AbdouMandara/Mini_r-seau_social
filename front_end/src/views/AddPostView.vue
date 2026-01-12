<template>
  <div class="add-post-view">
    <!-- Back arrow for desktop -->
    <div class="desktop-back desktop-only">
      <button class="btn-text back-link" @click="router.back()">
        <span class="material-symbols-rounded">arrow_back</span>
        Retour
      </button>
    </div>

    <div class="card">
      <h2 class="form-title">{{ isEditMode ? 'Modifier le post' : 'Créer un nouveau post' }}</h2>
      
      <form @submit.prevent="handleSubmit">
        <div class="input-group">
          <label>Image (optionnelle)</label>
          <div class="file-upload-custom" @click="$refs.fileInput.click()">
            <div v-if="!previewUrl" class="upload-placeholder">
              <span class="camera-icon">🖼️</span>
              <span>Ajouter une image</span>
            </div>
            <img v-else :src="previewUrl" class="upload-preview" />
          </div>
          <input 
            ref="fileInput"
            type="file" 
            style="display: none"
            accept="image/*"
            @change="handleFileChange"
          >
        </div>

        <div class="input-group">
          <label>Description (max 100 caractères)</label>
          <textarea 
            v-model="form.description" 
            class="input-control text-area" 
            maxlength="100"
            placeholder="Quoi de neuf ?"
            required
          ></textarea>
          <div class="char-count" :class="{ 'warning': form.description.length >= 90 }">
            {{ form.description.length }} / 100
          </div>
        </div>

        <div class="input-grid">
          <div class="input-group">
            <label>Tag (ex: etude, info...)</label>
            <input 
              v-model="form.tag" 
              list="existing-tags"
              class="input-control" 
              placeholder="Ajouter un tag..."
              required
            >
            <datalist id="existing-tags">
              <option v-for="t in existingTags" :key="t.tag" :value="t.tag">
                {{ t.total }} publications
              </option>
            </datalist>
          </div>

        </div>

        <div class="input-group toggle-group">
          <label>Autoriser les commentaires</label>
          <label class="switch">
            <input type="checkbox" v-model="form.allow_comments">
            <span class="slider round"></span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? (isEditMode ? 'Modification...' : 'Publication...') : (isEditMode ? 'Modifier' : 'Partager') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const loading = ref(false);
const previewUrl = ref(null);
const existingTags = ref([]);

// Determine if we're in edit mode
const isEditMode = computed(() => route.name === 'edit-post');
const postId = computed(() => route.params.postId);

const form = reactive({
  description: '',
  img_post: null,
  allow_comments: true,
  tag: ''
});

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.img_post = file;
    previewUrl.value = URL.createObjectURL(file);
  }
};

// Load existing post data for edit mode
const loadPostData = async () => {
  if (!isEditMode.value || !postId.value) return;
  
  try {
    loading.value = true;
    const res = await api.get(`/posts/${postId.value}`);
    const post = res.data;
    
    form.description = post.description || '';
    form.allow_comments = post.allow_comments;
    form.tag = post.tag || '';
    
    if (post.img_post) {
      previewUrl.value = post.img_post.startsWith('http') 
        ? post.img_post 
        : `${BASE_URL}/storage/${post.img_post}`;
    }
  } catch (err) {
    console.error('Error loading post:', err);
    alert('Erreur lors du chargement du post');
  } finally {
    loading.value = false;
  }
};

const fetchExistingTags = async () => {
  try {
    const res = await api.get('/tags');
    existingTags.value = res.data;
  } catch (err) {
    console.error('Error fetching tags', err);
  }
};

const handleSubmit = async () => {
  loading.value = true;
  
  const formData = new FormData();
  formData.append('description', form.description);
  formData.append('tag', form.tag);

  if (form.img_post instanceof File) {
    formData.append('img_post', form.img_post);
  }
  formData.append('allow_comments', form.allow_comments ? 1 : 0);

  try {
    if (isEditMode.value) {
      // Pour mettre à jour les données d'un post en  envoyant celà à l'api
      await api.post(`/posts/${postId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      // Pour ajouter un post en envoyant celà à l'api
      await api.post('/posts', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }
    
    Swal.fire({
        title: 'Succès !',
        text: isEditMode.value ? 'Votre post a été modifié.' : 'Votre post a été publié.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
    
    setTimeout(() => {
      router.push(`/${authStore.user.nom}/home`);
    }, 1500);

  } catch (err) {
    console.error(err);
    Swal.fire({
        title: 'Erreur',
        text: isEditMode.value ? 'Erreur lors de la modification.' : 'Erreur lors de la publication.',
        icon: 'error'
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadPostData();
  fetchExistingTags();
});
</script>

<style scoped>
.add-post-view {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px 15px 100px; /* Base padding + bottom space for mobile scroll */
}

.desktop-back {
    margin-bottom: 20px;
}

.back-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-weight: 600;
    transition: color 0.2s;
    border : none;
    background: none;
}

.back-link:hover {
    color: var(--primary-color);
}

.form-title {
    margin-bottom: 25px;
    font-size: 1.5rem;
    color: var(--text-color);
}

.text-area {
  min-height: 100px;
  resize: none;
}

.input-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.char-count {
  text-align: right;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 4px;
}

.char-count.warning {
  color: var(--error);
}

.file-upload-custom {
  border: 2px dashed var(--border-color);
  border-radius: 8px;
  padding: 20px;
  cursor: pointer;
  min-height: 150px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.upload-preview {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border-radius: 8px;
}

.toggle-group {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
}

/* Switch styling */
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: var(--input-bg);
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: var(--card-bg);
  transition: .4s;
}

input:checked + .slider {
  background-color: var(--primary-color);
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

.toast {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 24px;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  z-index: 1000;
}

.success-toast {
  background-color: var(--success);
}

@media (max-width: 767px) {
    .desktop-only {
        display: none !important;
    }
}

@media (min-width: 768px) {
    .desktop-only {
        display: block;
    }
}
</style>
