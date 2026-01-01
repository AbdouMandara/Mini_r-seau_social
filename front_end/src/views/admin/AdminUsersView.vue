<template>
  <div class="admin-users-view">
    <div class="view-header">
      <div class="header-content">
        <h2 class="page-title">Utilisateurs</h2>
      </div>

      <div class="filter-wrapper">
        <div class="select-container">
            <span class="material-symbols-rounded filter-icon">filter_list</span>
            <select id="user-filter" v-model="selectedFilter" class="custom-select">
                <option value="default">Tout</option>
                <option value="most_followers">Plus d' abonnés</option>
                <option value="most_following">Plus d' abonnements</option>
                <option value="most_posts">Plus de posts</option>
                <option value="most_interactions">Plus d'interactions</option>
                <option value="oldest">Plus ancien</option>
            </select>
            <span class="material-symbols-rounded arrow-icon">expand_more</span>
        </div>
      </div>
    </div>

    <div class="users-table-container">
      <table class="modern-table">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Posts</th>
            <th>Abonnements</th>
            <th>Abonnés</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in sortedUsers" :key="user.id">
            <td>
              <div class="user-cell">
                <img :src="getAvatar(user)" class="avatar-sm" />
                <div class="user-info">
                  <span class="name">{{ user.nom }}</span>
                  <span class="email" v-if="user.email">{{ user.email }}</span>
                </div>
              </div>
            </td>
            <td data-label="Posts"><span class="stat-number">{{ user.posts_count }}</span></td>
            <td data-label="Abonnements"><span class="stat-number">{{ user.following_count }}</span></td>
            <td data-label="Abonnés"><span class="stat-number">{{ user.followers_count }}</span></td>
            <td data-label="Statut">
                <span class="status-badge" :class="user.is_blocked ? 'blocked' : 'active'">
                    {{ user.is_blocked ? 'Bloqué' : 'Actif' }}
                </span>
            </td>
            <td data-label="Actions">
              <button 
                class="btn-icon" 
                :class="user.is_blocked ? 'btn-unblock' : 'btn-block'"
                @click="toggleBlock(user)"
                :title="user.is_blocked ? 'Débloquer' : 'Bloquer'"
              >
                <span class="material-symbols-rounded">
                    {{ user.is_blocked ? 'lock_open' : 'block' }}
                </span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="sortedUsers.length === 0 && !loading" class="empty-state">
        <p>Aucun utilisateur trouvé</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import Swal from 'sweetalert2';

const users = ref([]);
const loading = ref(true);
const selectedFilter = ref('default');

const sortedUsers = computed(() => {
    const usersCopy = [...users.value];
    
    switch(selectedFilter.value) {
        case 'most_posts':
            return usersCopy.sort((a, b) => b.posts_count - a.posts_count);
        case 'most_followers':
            return usersCopy.sort((a, b) => b.followers_count - a.followers_count);
        case 'most_following':
            return usersCopy.sort((a, b) => b.following_count - a.following_count);
        case 'oldest':
            return usersCopy.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        case 'most_interactions':
            return usersCopy.sort((a, b) => {
                const aInteractions = (a.likes_count || 0) + (a.comments_count || 0);
                const bInteractions = (b.likes_count || 0) + (b.comments_count || 0);
                return bInteractions - aInteractions;
            });
        default:
            return usersCopy;
    }
});

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
.admin-users-view {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    height: 100%;
}

.view-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f2f5;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1c1e21;
    margin-bottom: 4px;
}

.subtitle {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.filter-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filter-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-muted);
}

.select-container {
    position: relative;
    display: flex;
    align-items: center;
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
    padding: 10px 40px 10px 40px; /* Space for icons */
    font-size: 0.9rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background-color: #f9fafb;
    color: #374151;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 180px;
}

.custom-select:hover {
    background-color: #f3f4f6;
    border-color: #d1d5db;
}

.custom-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1);
    background-color: white;
}

.users-table-container {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px; /* Spacing between rows */
}

.modern-table th {
    text-align: center;
    padding: 0 15px 10px 15px;
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    
    &:first-child{
        text-align: left;
    }
}

.modern-table tbody tr {
    transition: transform 0.2s;
}

.modern-table tbody tr:hover td {
    background-color: #f8fafc;
}

.modern-table td {
    padding: 16px 15px;
    background: white;
    vertical-align: middle;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
    text-align: center;
}

.modern-table td:first-child {
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
    border-left: 1px solid #f3f4f6;
}

.modern-table td:last-child {
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
    border-right: 1px solid #f3f4f6;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar-sm {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.user-info {
    display: flex;
    flex-direction: column;
}

.name { 
    font-weight: 600; 
    color: #1c1e21;
    font-size: 0.95rem;
}

.email { 
    font-size: 0.8rem; 
    color: var(--text-muted); 
}

.stat-number {
    font-weight: 600;
    color: #4b5563;
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.85rem;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active { background: #dcfce7; color: #166534; }
.status-badge.blocked { background: #fee2e2; color: #991b1b; }

.btn-icon {
    border: none;
    background: #f9fafb;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-block:hover { 
    background: #fee2e2; 
    color: #dc2626; 
}

.btn-unblock:hover { 
    background: #d1fae5; 
    color: #059669; 
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
}

/* Scrollbar styling for table container */
.users-table-container::-webkit-scrollbar {
    height: 6px;
}

.users-table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.users-table-container::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.users-table-container::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .modern-table thead {
        display: none;
    }
    
    .modern-table, .modern-table tbody, .modern-table tr, .modern-table td {
        display: block;
        width: 100%;
    }
    
    .modern-table tr {
        background: white;
        margin-bottom: 20px;
        border-radius: 12px;
        border: 1px solid #f3f4f6;
        padding: 15px;
    }

    .modern-table td {
        text-align: left; /* Reset center alignment */
        padding: 8px 0;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modern-table td:first-child {
        border-radius: 0;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 12px;
        margin-bottom: 8px;
    }
    
    .modern-table td:last-child {
        border-radius: 0;
        border-top: 1px solid #f3f4f6;
        padding-top: 12px;
        margin-top: 8px;
        justify-content: flex-end;
    }
    
    .modern-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    
    /* Hide empty pseudo-element for first cell (cleaner look) */
    .modern-table td:first-child::before {
        display: none;
    }

    .user-cell {
        width: 100%;
    }
}
</style>
