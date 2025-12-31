<template>
  <div class="admin-users">
    <h2 class="page-title">Utilisateurs</h2>

    <div class="users-table-container card">
      <table class="modern-table">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Région</th>
            <th>Posts</th>
            <th>Abonnés</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>
              <div class="user-cell">
                <img :src="getAvatar(user)" class="avatar-sm" />
                <div class="user-info">
                  <span class="name">{{ user.nom }}</span>
                  <span class="email" v-if="user.email">{{ user.email }}</span>
                </div>
              </div>
            </td>
            <td>{{ user.region }}</td>
            <td>{{ user.posts_count }}</td>
            <td>{{ user.followers_count }}</td>
            <td>
                <span class="status-badge" :class="user.is_blocked ? 'blocked' : 'active'">
                    {{ user.is_blocked ? 'Bloqué' : 'Actif' }}
                </span>
            </td>
            <td>
              <button 
                class="btn-icon" 
                :class="user.is_blocked ? 'btn-unblock' : 'btn-block'"
                @click="toggleBlock(user)"
                title="Bloquer/Débloquer"
              >
                <span class="material-symbols-rounded">
                    {{ user.is_blocked ? 'lock_open' : 'block' }}
                </span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination could go here -->
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import Swal from 'sweetalert2';

const users = ref([]);
const loading = ref(true);

const fetchUsers = async () => {
    loading.value = true;
    try {
        const res = await api.get('/admin/users');
        users.value = res.data.data; // Pagination data
    } catch (err) {
        console.error('Fetch users error', err);
    } finally {
        loading.value = false;
    }
};

const getAvatar = (user) => {
    if (!user.photo_profil) return `https://ui-avatars.com/api/?name=${user.nom}`;
    return user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`;
};

const toggleBlock = async (user) => {
    const action = user.is_blocked ? 'Débloquer' : 'Bloquer';
    
    const { value: password } = await Swal.fire({
        title: `${action} ${user.nom} ?`,
        text: "Entrez votre mot de passe administrateur pour confirmer.",
        input: 'password',
        inputPlaceholder: 'Mot de passe Admin',
        showCancelButton: true,
        confirmButtonColor: user.is_blocked ? '#10b981' : '#ef4444',
        confirmButtonText: action,
        cancelButtonText: 'Annuler',
        inputValidator: (value) => {
            if (!value) {
                return 'Mot de passe requis !';
            }
        }
    });

    if (password) {
        try {
            const res = await api.post(`/admin/users/${user.id}/toggle-block`, { 
                admin_password: password 
            });
            
            user.is_blocked = res.data.is_blocked;
            Swal.fire({
                title: 'Succès',
                text: res.data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire('Erreur', err.response?.data?.message || 'Une erreur est survenue', 'error');
        }
    }
};

onMounted(fetchUsers);
</script>

<style scoped>
.page-title { margin-bottom: 20px; font-size: 1.5rem; }

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table th {
    text-align: left;
    padding: 15px;
    color: var(--text-muted);
    font-size: 0.85rem;
    font-weight: 600;
    border-bottom: 1px solid #eee;
}

.modern-table td {
    padding: 15px;
    border-bottom: 1px solid #f5f5f5;
    vertical-align: middle;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.name { font-weight: 600; color: #1c1e21; }
.email { font-size: 0.8rem; color: var(--text-muted); }

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active { background: #d1fae5; color: #059669; }
.status-badge.blocked { background: #fee2e2; color: #dc2626; }

.btn-icon {
    border: none;
    background: #f3f4f6;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-block:hover { background: #fee2e2; color: #dc2626; }
.btn-unblock:hover { background: #d1fae5; color: #059669; }
</style>
