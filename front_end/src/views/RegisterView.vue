<template>
  <div class="auth-page">
    <div class="circles">
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
    </div>

    <div class="auth-card glass">
      <div class="auth-header">
        <h1 class="logo">Inscription</h1>
        <p class="subtitle">Créez votre profil en quelques secondes</p>
      </div>

      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="input-block">
          <div class="input-wrapper" :class="{ 'focused': focusedField === 'nom', 'has-error': errors.nom }">
            <span class="material-symbols-rounded icon">person</span>
            <input
              v-model="form.nom"
              type="text"
              placeholder="Nom d'utilisateur"
              @focus="focusedField = 'nom'"
              @blur="focusedField = null"
              required
            >
          </div>
          <Transition name="fade">
            <span v-if="errors.nom" class="error-msg">{{ errors.nom[0] }}</span>
          </Transition>
        </div>

        <div class="input-block">
          <div class="input-wrapper" :class="{ 'focused': focusedField === 'password', 'has-error': errors.password }">
            <span class="material-symbols-rounded icon">lock</span>
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Mot de passe au moins 8 caractères"
              @focus="focusedField = 'password'"
              @blur="focusedField = null"
              required
            >
            <span class="material-symbols-rounded eye-icon" @click="showPassword = !showPassword">
                {{ showPassword ? 'visibility_off' : 'visibility' }}
            </span>
          </div>
          <Transition name="fade">
            <span v-if="errors.password" class="error-msg">{{ errors.password[0] }}</span>
          </Transition>
        </div>

        <div class="input-block">
          <div class="file-upload-premium" @click="$refs.fileInput.click()">
            <div v-if="!previewUrl" class="upload-placeholder">
              <span class="material-symbols-rounded">add_a_photo</span>
              <span>Choisir une photo de profil (Facultatif)</span>
            </div>
            <div v-else class="preview-container">
                <img :src="previewUrl" alt="Aperçu de la photo de profil" class="upload-preview" />
                <div class="change-overlay">Changer</div>
            </div>
          </div>
          <input
            ref="fileInput"
            type="file"
            style="display: none"
            accept="image/*"
            @change="handleFileChange"
          >
          <Transition name="fade">
            <span v-if="errors.photo_profil" class="error-msg">{{ errors.photo_profil[0] }}</span>
          </Transition>
        </div>

        <p v-if="globalError" class="error-msg central">{{ globalError }}</p>

        <button type="submit" class="btn btn-primary btn-auth" :disabled="loading">
          <div v-if="loading" class="mini-loader"></div>
          <span v-else>S'inscrire</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Déjà membre ? <router-link to="/login">Me connecter</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const loading = ref(false);
const errors = ref({});
const globalError = ref(null);
const previewUrl = ref(null);
const focusedField = ref(null);
const showPassword = ref(false);

const form = reactive({
  nom: '',
  password: '',
  photo_profil: null
});

const validateInput = (field) => {
  if (field === 'nom') {
    if (!form.nom) errors.value.nom = ['Requis'];
    else if (form.nom.length < 3) errors.value.nom = ['Trop court au moins 3 caractères'];
    else delete errors.value.nom;
  }

  if (field === 'password') {
    if (!form.password) errors.value.password = ['Requis'];
    else if (form.password.length < 8) errors.value.password = ['Minimum 8 caractères.'];
    else delete errors.value.password;
  }
};

watch(() => form.nom, () => validateInput('nom'));
watch(() => form.password, () => validateInput('password'));

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.photo_profil = file;
    previewUrl.value = URL.createObjectURL(file);
    delete errors.value.photo_profil;
  }
};

const handleRegister = async () => {
  loading.value = true;
  errors.value = {};
  globalError.value = null;

  const formData = new FormData();
  formData.append('nom', form.nom);
  formData.append('password', form.password);
  
  if (form.photo_profil) {
    formData.append('photo_profil', form.photo_profil);
  }

  try {
    const res = await authStore.register(formData);
    const username = (res.user.slug || res.user.nom).replace(/ /g, '_');
    router.push(`/${username}/home`);
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      globalError.value = err.response?.data?.message || 'Erreur lors de l\'inscription';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-color);
  position: relative;
  overflow: hidden;
  padding: 20px;
}

.circles {
    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 0;
}

.circle {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
}

.circle-1 {
    width: 300px;
    height: 300px;
    background: rgba(24, 119, 242, 0.15);
    top: -100px;
    right: -100px;
}

.circle-2 {
    width: 250px;
    height: 250px;
    background: rgba(24, 119, 242, 0.1);
    bottom: -50px;
    left: -50px;
}

.auth-card {
  width: 100%;
  max-width: 440px;
  padding: 40px;
  background: var(--card-bg);
  backdrop-filter: blur(10px);
  border-radius: 30px;
  z-index: 1;
  border: 1px solid var(--border-color);
}

.auth-header {
    text-align: center;
    margin-bottom: 25px;
}

.logo {
  color: var(--primary-color);
  font-size: 2.5em;
  font-weight: 900;
  letter-spacing: -2px;
  margin-bottom: 5px;
}

.subtitle {
    color: var(--text-muted);
    font-size: 0.95rem;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.input-block {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.section-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-muted);
    margin-bottom: 2px;
}

.input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--input-bg);
    padding: 0 20px;
    height: 52px;
    border-radius: 18px;
    border: 2px solid var(--border-color);
    transition: all 0.3s;
}

.input-wrapper.focused {
    background: var(--card-bg);
    border-color: var(--primary-color);
}

.input-wrapper.has-error {
    border-color: var(--error);
}

.input-wrapper .icon {
    color: var(--text-muted);
    font-size: 20px;
}

.input-wrapper input, .input-wrapper select {
    flex: 1;
    background: none;
    border: none;
    font-size: 1rem;
    font-weight: 500;
    outline: none;
    color: var(--text-color);
}

.eye-icon {
    cursor: pointer;
    color: var(--text-muted);
    font-size: 20px;
}

.file-upload-premium {
    height: 100px;
    border: 2px dashed var(--text-muted);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    overflow: hidden;
}

.file-upload-premium:hover {
    border-color: var(--primary-color);
    background: rgba(24, 119, 242, 0.05);
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
}

.preview-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.upload-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.change-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s;
}

.preview-container:hover .change-overlay {
    opacity: 1;
}

.error-msg {
    color: var(--error);
    font-size: 0.75rem;
    padding-left: 10px;
}

.error-msg.central {
    text-align: center;
}

.btn-auth {
    height: 52px;
    border-radius: 18px;
    font-size: 1.05rem;
    font-weight: 700;
    margin-top: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    border: none;
    transition: background-color 0.2s;
}

.btn-auth:hover {
    background-color: var(--primary-color);
}

.auth-footer {
    margin-top: 25px;
    text-align: center;
    font-size: 0.95rem;
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 700;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}
</style>
