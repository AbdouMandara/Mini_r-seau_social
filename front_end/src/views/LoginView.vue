<template>
  <div class="auth-page">
    <div class="circles">
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
    </div>
    
    <div class="auth-card glass">
      <div class="auth-header">
        <h1 class="logo">Connexion</h1>
        <p class="subtitle">Heureux de vous revoir !</p>
      </div>

      <form @submit.prevent="handleLogin" class="auth-form">
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
            <span v-if="errors.nom" class="error-msg">{{ errors.nom }}</span>
          </Transition>
        </div>

        <div class="input-block">
          <div class="input-wrapper" :class="{ 'focused': focusedField === 'password', 'has-error': errors.password }">
            <span class="material-symbols-rounded icon">lock</span>
            <input 
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="Mot de passe"
              @focus="focusedField = 'password'"
              @blur="focusedField = null"
              required
            >
            <span class="material-symbols-rounded eye-icon" @click="showPassword = !showPassword">
                {{ showPassword ? 'visibility_off' : 'visibility' }}
            </span>
          </div>
          <Transition name="fade">
            <span v-if="errors.password" class="error-msg">{{ errors.password }}</span>
          </Transition>
        </div>

        <p v-if="error" class="error-msg central">{{ error }}</p>

        <button type="submit" class="btn btn-primary btn-auth" :disabled="loading">
          <div v-if="loading" class="mini-loader"></div>
          <span v-else>Se connecter</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Nouveau ici ? <router-link to="/register">Rejoindre l'aventure</router-link></p>
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
const error = ref(null);
const focusedField = ref(null);
const showPassword = ref(false);

const errors = reactive({
  nom: '',
  password: ''
});

const form = reactive({
  nom: '',
  password: ''
});

const validateInput = (field) => {
  if (field === 'nom') {
    if (!form.nom) errors.nom = 'Requis';
    else errors.nom = '';
  }
  if (field === 'password') {
    if (!form.password) errors.password = 'Requis';
    else errors.password = '';
  }
};

watch(() => form.nom, () => validateInput('nom'));
watch(() => form.password, () => validateInput('password'));

const handleLogin = async () => {
  validateInput('nom');
  validateInput('password');
  
  if (errors.nom || errors.password) return;

  loading.value = true;
  error.value = null;

  try {
    const res = await authStore.login(form);
    
    if (res.user.is_admin) {
        router.push('/admin/dashboard');
        return;
    }

    const username = (res.user.slug || res.user.nom).replace(/ /g, '_');
    router.push(`/${username}/home`);
  } catch (err) {
    error.value = err.response?.data?.message || 'Identifiants incorrects';
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
  background: #f8fafc;
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
  max-width: 420px;
  padding: 40px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border-radius: 30px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.05);
  z-index: 1;
  border: 1px solid rgba(255,255,255,0.5);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
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
    gap: 20px;
}

.input-block {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f1f5f9;
    padding: 0 20px;
    height: 55px;
    border-radius: 18px;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.input-wrapper.focused {
    background: white;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(24, 119, 242, 0.1);
}

.input-wrapper.has-error {
    border-color: var(--error);
}

.input-wrapper .icon {
    color: #94a3b8;
    font-size: 20px;
}

.input-wrapper input {
    flex: 1;
    background: none;
    border: none;
    font-size: 1rem;
    font-weight: 500;
    outline: none;
}

.eye-icon {
    cursor: pointer;
    color: #94a3b8;
    font-size: 20px;
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
    height: 55px;
    border-radius: 18px;
    font-size: 1.05rem;
    font-weight: 700;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    border: none;
    transition: background-color 0.2s;
}

.btn-auth:hover {
    transform: none;
    background-color: #166fe5; /* Slightly darker */
}

.auth-footer {
    margin-top: 30px;
    text-align: center;
    font-size: 0.95rem;
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 700;
}

.mini-loader {
    width: 24px;
    height: 24px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}
</style>
