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
          <tr v-for="user in sortedUsers" :key="user.id" :class="{'expanded': expandedUserId === user.id}">
            <td>
              <div class="user-cell">
                <div class="user-info-group">
                    <img :src="getAvatar(user)" class="avatar-sm" />
                    <div class="user-info">
                    <span class="name">{{ user.nom }}</span>
                    </div>
                </div>
                <!-- Expansion Arrow (Mobile Only) -->
                <button class="expand-btn mobile-only" @click.stop="toggleDetails(user.id)">
                    <span class="material-symbols-rounded">expand_more</span>
                </button>
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
const expandedUserId = ref(null);
const loading = ref(true);
const selectedFilter = ref('default');

const toggleDetails = (id) => {
    expandedUserId.value = expandedUserId.value === id ? null : id;
};

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
    background: var(--card-bg);
    border-radius: 20px;
    padding: 25px;
    border: 1px solid var(--border-color);
    height: 100%;
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
    padding: 10px 40px 10px 40px; 
    font-size: 0.9rem;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    background-color: var(--input-bg);
    color: var(--text-color);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 180px;
}

.custom-select:hover {
    background-color: var(--secondary-color);
}

.custom-select:focus {
    outline: none;
    border-color: var(--primary-color);
    background-color: var(--card-bg);
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
    background-color: var(--secondary-color);
}
/* Disable hover on mobile to match user preference "pas background gray" */
@media (max-width: 768px) {
    .modern-table tbody tr:hover td {
        background-color: white;
    }
}

.modern-table td {
    padding: 16px 15px;
    background: var(--card-bg);
    vertical-align: middle;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    text-align: center;
    color: var(--text-color);
}

.modern-table td:first-child {
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
    border-left: 1px solid var(--border-color);
}

.modern-table td:last-child {
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
    border-right: 1px solid var(--border-color);
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
    border: 2px solid var(--card-bg);
}

.user-info {
    display: flex;
    flex-direction: column;
    text-align: left; /* Ensure left alignment */
}

.user-info-group {
    display: flex;
    align-items: center;
    gap: 15px;
}

.name { 
    font-weight: 600; 
    color: var(--text-color);
    font-size: 0.95rem;
}

.email { 
    font-size: 0.8rem; 
    color: var(--text-muted); 
}

.stat-number {
    font-weight: 600;
    color: var(--text-color);
    background: var(--input-bg);
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

.status-badge.active { background: rgba(66, 183, 42, 0.1); color: #42b72a; }
.status-badge.blocked { background: rgba(240, 40, 73, 0.1); color: #f02849; }

.btn-icon {
    border: none;
    background: var(--input-bg);
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
    background: var(--input-bg);
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
        background: var(--card-bg);
        margin-bottom: 15px;
        border-radius: 16px;
        border: 1px solid var(--border-color);
        padding: 0;
        overflow: hidden; 
        transition: all 0.3s ease;
    }

    /* First cell (Header of card) */
    .modern-table td:first-child {
        border-bottom: none;
        padding: 15px;
        margin: 0;
        background: var(--card-bg);
        z-index: 2;
        position: relative;
    }

    /* Hidden details cells */
    .modern-table td:not(:first-child) {
        max-height: 0;
        overflow: hidden;
        padding: 0 15px;
        margin: 0;
        border: none;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Expanded State */
    .modern-table tr.expanded td:not(:first-child) {
        max-height: 60px; /* Adjust based on content */
        opacity: 1;
        padding: 10px 15px;
        border-top: 1px solid #f9fafb;
    }

     /* Specific adjustments for Actions cell to fit content */
    .modern-table tr.expanded td[data-label="Actions"] {
         padding-bottom: 20px; 
    }

    .modern-table td:not(:first-child) {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
    }
    
    /* Status alignment: Label left, Badge completely right */
    .modern-table td[data-label="Statut"] {
        justify-content: space-between; 
    }

    .modern-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.85rem;
        min-width: 100px; /* align labels */
    }

    .user-cell {
        width: 100%;
        display: flex;
        justify-content: space-between; /* Space for arrow */
        align-items: center;
    }
    
    .expand-btn {
        background: var(--input-bg);
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex; 
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.3s;
        color: var(--text-color);
    }

    .modern-table tr.expanded .expand-btn {
        transform: rotate(180deg);
        /* User requested no background change */
    }

    .user-info-group {
        /* Already defined globally, but ensure it takes space properly if needed */
        flex-grow: 1;
    }
}
</style>
