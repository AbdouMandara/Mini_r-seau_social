<template>
  <div class="blocked-page">
    <div class="blocked-content animate-fade-in">
        <div class="icon-container">
            <span class="material-symbols-rounded">block</span>
        </div>
        <h1>Compte Temporairement Bloqué</h1>
        <p class="reason">Non respect des conditions d'utilisation</p>
        <p class="description">
            Votre compte a été suspendu par un administrateur suite à une violation de nos règles communautaires.
        </p>
        
        <div class="actions">
            <a href="mailto:abdoumandara@gmail.com?subject=Contestation de blocage" class="btn btn-primary-modern">
                <span class="material-symbols-rounded">mail</span>
                Contacter l'administrateur
            </a>
            
            <button @click="logout" class="btn btn-ghost">
                Se déconnecter
            </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const logout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>

<style scoped>
.blocked-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fdf2f2;
    padding: 20px;
}

.blocked-content {
    text-align: center;
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(220, 38, 38, 0.1);
    max-width: 500px;
}

.icon-container {
    width: 80px;
    height: 80px;
    background: #fee2e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.icon-container span {
    font-size: 40px;
    color: #dc2626;
}

h1 {
    color: #1c1e21;
    margin-bottom: 10px;
    font-size: 1.8rem;
}

.reason {
    color: #dc2626;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 15px;
}

.description {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 30px;
}

.actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}

.btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
}

.btn-primary-modern {
    background: #dc2626;
    color: white;
}

.btn-primary-modern:hover {
    background: #b91c1c;
}

.btn-ghost {
    background: transparent;
    color: #6b7280;
}

.btn-ghost:hover {
    background: #f3f4f6;
    color: #1c1e21;
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
