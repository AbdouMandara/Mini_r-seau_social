<template>
  <div class="profile-view">
    <div v-if="loading" class="loader-wrapper">
      <Loader />
    </div>
    
    <div v-else-if="user" class="profile-container">
      <!-- Profile Header (TikTok Style Modern) -->
      <div class="profile-header card">
        <div class="header-main">
            <div class="avatar-wrapper">
              <img :src="profileImageUrl" class="large-avatar" @error="handleAvatarError" />
            </div>
            <div class="user-meta">
              <h2 class="username">@{{ user.nom }}</h2>
              <div class="action-buttons">
                <button v-if="isMyProfile" class="btn btn-secondary edit-btn" @click="openEditModal">Modifier le profil</button>
                <button v-else class="btn btn-primary follow-btn">Suivre</button>
                <button class="btn btn-icon share-profile" @click="handleShareProfile"><span class="material-symbols-rounded">share</span></button>
              </div>
            </div>
        </div>

        <div class="profile-stats">
          <div class="stat-item">
            <span class="count">{{ posts.length }}</span>
            <span class="label">Publications</span>
          </div>
          <div class="stat-item">
            <span class="count">0</span>
            <span class="label">Abonnés</span>
          </div>
          <div class="stat-item">
            <span class="count">0</span>
            <span class="label">Abonnements</span>
          </div>
        </div>

        <div class="profile-bio">
          <p class="location"><span class="material-symbols-rounded">location_on</span> {{ user.region }}</p>
          <p v-if="user.description" class="bio-text">{{ user.description }}</p>
          <p v-else-if="isMyProfile" class="bio-text muted">[Veuillez modifier votre profil pour choisir une description]</p>
        </div>
      </div>

      <!-- User Interactions (Own Profile Only) -->
      <div v-if="isMyProfile" class="user-interactions card">
        <h3 class="section-title">Vos Interactions</h3>
        <div class="interactions-grid">
           <div class="inter-item">
             <span class="inter-icon">❤️</span>
             <span>{{ user.likes_count || 0 }} Likes</span>
           </div>
           <div class="inter-item">
             <span class="inter-icon">💬</span>
             <span>{{ user.comments_count || 0 }} Commentaires</span>
           </div>
        </div>
      </div>

      <!-- User Posts Feed -->
      <div class="user-feed">
        <div class="feed-tabs">
            <button class="tab-item active">Posts</button>
            <button class="tab-item"><span class="material-symbols-rounded">lock</span></button>
        </div>

        <div v-if="posts.length === 0" class="empty-feed">
          <div class="empty-icon">📂</div>
          <p>Aucune publication pour le moment.</p>
        </div>
        <div v-else class="posts-feed">
           <PostCard 
            v-for="post in posts" 
            :key="post.id_post" 
            :post="post" 
            @refresh="fetchUserPosts"
            @open-comments="openComments"
          />
        </div>
      </div>
    </div>

    <!-- Comments Drawer -->
    <CommentDrawer 
      :is-open="isDrawerOpen" 
      :post-id="activePostId" 
      @close="isDrawerOpen = false"
      @comment-added="fetchUserPosts"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute } from 'vue-router';
import api, { BASE_URL } from '@/utils/api';
import PostCard from '@/components/PostCard.vue';
import CommentDrawer from '@/components/CommentDrawer.vue';
import Loader from '@/components/Loader.vue';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const route = useRoute();
const user = ref(null);
const posts = ref([]);
const loading = ref(true);

const isDrawerOpen = ref(false);
const activePostId = ref(null);

const isMyProfile = computed(() => {
  if (!route.params.target_name) return true;
  return authStore.user?.nom === route.params.target_name;
});

const openComments = (post) => {
  activePostId.value = post.id_post;
  isDrawerOpen.value = true;
};

const profileImageUrl = computed(() => {
  if (user.value?.photo_profil) {
    if (user.value.photo_profil.startsWith('http')) return user.value.photo_profil;
    return `${BASE_URL}/storage/${user.value.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + (user.value?.nom || 'User');
});

const handleAvatarError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=' + (user.value?.nom || 'User');
};

const fetchUserPosts = async () => {
    loading.value = true;
    try {
        const username = route.params.target_name || authStore.user?.nom;
        
        const userRes = await api.get(`/users/profile/${username}`);
        user.value = userRes.data;

        const postsRes = await api.get(`/users/${user.value.id}/posts`);
        posts.value = postsRes.data;
    } catch (err) {
        console.error('Fetch profile posts error', err);
    } finally {
        loading.value = false;
    }
};

const openEditModal = async () => {
    const { value: formValues } = await Swal.fire({
        title: 'Modifier le profil',
        html: `
            <div class="edit-profile-form" style="text-align: left;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nom</label>
                <input id="swal-nom" class="swal2-input" value="${user.value.nom}" style="margin: 0 0 15px 0; width: 100%;">
                
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Région</label>
                <input id="swal-region" class="swal2-input" value="${user.value.region || ''}" style="margin: 0 0 15px 0; width: 100%;">
                
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                <textarea id="swal-description" class="swal2-textarea" style="margin: 0 0 15px 0; width: 100%; height: 80px;">${user.value.description || ''}</textarea>
                
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Photo de profil</label>
                <input id="swal-photo" type="file" class="swal2-file" style="margin: 0 0 5px 0; width: 100%;">
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Enregistrer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#1877f2',
        preConfirm: () => {
            return {
                nom: document.getElementById('swal-nom').value,
                region: document.getElementById('swal-region').value,
                description: document.getElementById('swal-description').value,
                photo: document.getElementById('swal-photo').files[0]
            }
        }
    });

    if (formValues) {
        try {
            const formData = new FormData();
            formData.append('nom', formValues.nom);
            formData.append('region', formValues.region);
            formData.append('description', formValues.description);
            if (formValues.photo) {
                formData.append('photo_profil', formValues.photo);
            }

            const res = await api.post('/user/update', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            authStore.user = res.data.user;
            localStorage.setItem('user', JSON.stringify(res.data.user));
            user.value = res.data.user;

            Swal.fire('Succès', 'Profil mis à jour !', 'success');
        } catch (err) {
            console.error(err);
            Swal.fire('Erreur', err.response?.data?.message || 'Erreur lors de la mise à jour', 'error');
        }
    }
};

const handleShareProfile = () => {
    const profileUrl = `${window.location.origin}/${user.value.nom}/profil/${authStore.user.nom}`;
    const message = `Suis-moi sur !pozterr voilà mon compte : ${profileUrl}`;
    
    Swal.fire({
        title: 'Partager le profil',
        html: `
            <div style="display: flex; flex-direction: column; gap: 10px; padding-top: 10px;">
                <button id="share-wa" class="swal2-confirm swal2-styled" style="background-color: #25D366; margin: 0;">WhatsApp</button>
                <button id="share-copy" class="swal2-confirm swal2-styled" style="background-color: #1877f2; margin: 0;">Copier le lien</button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('share-wa').onclick = () => {
                window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`, '_blank');
            };
            document.getElementById('share-copy').onclick = () => {
                navigator.clipboard.writeText(message);
                Swal.fire({ title: 'Copié !', icon: 'success', timer: 1000, showConfirmButton: false });
            };
        }
    });
};

onMounted(async () => {
    if (!authStore.user) {
        await authStore.fetchProfile();
    }
    fetchUserPosts();
});

watch(() => route.params.target_name, fetchUserPosts);
</script>

<style scoped>
.profile-view {
  padding: 10px 0 80px;
}

.loader-wrapper {
    height: 60vh;
    display: flex;
    align-items: center;
}

.profile-header {
  padding: 20px;
  margin-bottom: 2px;
  border-radius: 0;
  box-shadow: none;
  background: transparent;
}

.header-main {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.large-avatar {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
}

.user-meta {
    flex: 1;
}

.username {
  font-size: 1.4rem;
  font-weight: 800;
  margin-bottom: 12px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

.edit-btn, .follow-btn {
    padding: 8px 24px;
    font-weight: 600;
    border-radius: 4px;
    font-size: 0.9rem;
}

.btn-icon {
    padding: 6px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    display: flex;
}

.profile-stats {
  display: flex;
  gap: 25px;
  margin-bottom: 20px;
  padding: 0 5px;
}

.stat-item {
  display: flex;
  gap: 5px;
  align-items: baseline;
}

.stat-item .count {
  font-weight: 800;
  font-size: 1.1rem;
}

.stat-item .label {
  font-size: 0.9rem;
  color: var(--text-muted);
}

.profile-bio {
    padding: 0 5px;
}

.location {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 8px;
}

.location span { font-size: 16px; }

.bio-text {
  font-size: 0.95rem;
  line-height: 1.4;
}

.user-feed {
    margin-top: 10px;
}

.feed-tabs {
    display: flex;
    border-bottom: 1px solid #eee;
    margin-bottom: 15px;
}

.tab-item {
    flex: 1;
    padding: 12px;
    background: none;
    border: none;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    justify-content: center;
    border-bottom: 2px solid transparent;
}

.tab-item.active {
    color: var(--text-main);
    border-bottom-color: var(--text-main);
}

.empty-feed {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.posts-feed {
    max-width: 600px;
    margin: 0 auto;
}

.user-interactions {
    margin-bottom: 25px;
    padding: 20px;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: var(--text-color);
}

.interactions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.inter-item {
    background: #f0f2f5;
    padding: 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 0.9rem;
}

.inter-icon {
    font-size: 1.2rem;
}

.bio-text.muted {
    font-style: italic;
    color: var(--text-muted);
}

@media (min-width: 768px) {
    .profile-container {
        max-width: 600px;
        margin: 0 auto;
    }
}
</style>
