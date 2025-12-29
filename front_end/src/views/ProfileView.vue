<template>
  <div class="profile-view">
    <div v-if="loading" class="loader-wrapper">
      <Loader />
    </div>
    
    <div v-else-if="user" class="profile-container animate-fade-in">
      <!-- Modern Profile Header (Dribbble Style) -->
      <div class="profile-header-new card">
        <div class="header-main-content">
          <div class="avatar-container">
            <div class="avatar-ring">
              <img :src="profileImageUrl" class="profile-avatar-large" @error="handleAvatarError" />
            </div>
          </div>
          
          <div class="user-info-section">
            <div class="name-row">
              <h2 class="display-name">{{ user.nom }}</h2>
              <span class="verified-badge" v-if="posts.length > 5">
                <span class="material-symbols-rounded">check_circle</span>
              </span>
            </div>
            <p class="handle">@{{ user.nom.toLowerCase().replace(/\s+/g, '_') }}</p>
            
            <div class="profile-actions">
              <template v-if="isMyProfile">
                <button class="btn btn-edit-modern" @click="openEditModal">
                  <span class="material-symbols-rounded">edit</span>
                  Modifier
                </button>
                <button class="btn btn-icon-modern" @click="handleShareProfile">
                  <span class="material-symbols-rounded">share</span>
                </button>
              </template>
              <template v-else>
                <button class="btn btn-primary-modern">Suivre</button>
                <button class="btn btn-icon-modern" @click="handleShareProfile">
                  <span class="material-symbols-rounded">share</span>
                </button>
              </template>
            </div>
          </div>
        </div>

        <div class="profile-metrics">
          <div class="metric-card">
            <span class="metric-value">{{ posts.length }}</span>
            <span class="metric-label">Posts</span>
          </div>
          <div class="metric-card">
            <span class="metric-value">0</span>
            <span class="metric-label">Abonnés</span>
          </div>
          <div class="metric-card">
            <span class="metric-value">0</span>
            <span class="metric-label">Abonnements</span>
          </div>
        </div>

        <div class="profile-bio-new">
          <div class="bio-meta">
            <span class="meta-item">
              <span class="material-symbols-rounded">location_on</span>
              {{ user.region || 'Terre' }}
            </span>
            <span class="meta-item">
              <span class="material-symbols-rounded">calendar_today</span>
              Depuis {{ new Date(user.created_at).getFullYear() }}
            </span>
          </div>
          <p v-if="user.description" class="bio-content">{{ user.description }}</p>
          <p v-else-if="isMyProfile" class="bio-content placeholder">Ajoutez une bio pour vous présenter au monde...</p>
        </div>
      </div>

      <!-- Feed Tabs Navigation -->
      <div class="feed-navigation">
        <button class="nav-tab" :class="{ active: activeTab === 'posts' }" @click="activeTab = 'posts'">
          <span class="material-symbols-rounded">grid_view</span>
          Publications
        </button>
        <button class="nav-tab" :class="{ active: activeTab === 'interactions' }" @click="activeTab = 'interactions'">
          <span class="material-symbols-rounded">bolt</span>
          Interactions
        </button>
      </div>

      <!-- Conditional Content -->
      <div class="tab-content-container">
        <!-- Posts Feed -->
        <Transition name="slide-fade" mode="out-in">
          <div v-if="activeTab === 'posts'" key="posts" class="posts-section">
            <div v-if="posts.length === 0" class="empty-state-modern">
              <div class="empty-art">📸</div>
              <h3>Pas encore de posts</h3>
              <p>Commencez à partager vos moments avec la communauté.</p>
              <button v-if="isMyProfile" class="btn btn-primary" @click="router.push(`/${authStore.user.nom}/add_post`)">Créer mon premier post</button>
            </div>
            <div v-else class="feed-grid">
              <PostCard 
                v-for="post in posts" 
                :key="post.id_post" 
                :post="post" 
                @refresh="fetchUserPosts"
                @open-comments="openComments"
              />
            </div>
          </div>

          <!-- Interactions List (Functional) -->
          <div v-else-if="activeTab === 'interactions'" key="interactions" class="interactions-section">
            <div v-if="interactionsLoading" class="mini-loader">
              <Loader />
            </div>
            <div v-else-if="interactions.length === 0" class="empty-state-modern">
              <div class="empty-art">✨</div>
              <h3>Aucune interaction</h3>
              <p>Vos likes et commentaires apparaîtront ici.</p>
            </div>
            <div v-else class="interactions-timeline">
              <div v-for="item in interactions" :key="item.id" class="interaction-node card">
                <div class="node-icon" :class="item.type">
                  <span class="material-symbols-rounded">
                    {{ item.type === 'like' ? 'favorite' : 'chat_bubble' }}
                  </span>
                </div>
                <div class="node-content">
                  <p class="node-text">
                    Vous avez <strong>{{ item.type === 'like' ? 'aimé' : 'commenté' }}</strong> 
                    le post de <strong>{{ item.post?.user?.nom || 'quelqu\'un' }}</strong>
                  </p>
                  <p v-if="item.type === 'comment'" class="comment-preview">"{{ item.contenu }}"</p>
                  
                  <div class="post-preview-card" v-if="item.post" @click="goToPost(item.post)">
                    <img v-if="item.post.img_post" :src="formatPostImg(item.post.img_post)" class="preview-img" />
                    <p class="preview-text">{{ truncate(item.post.description, 60) }}</p>
                  </div>
                  <span class="node-time">{{ formatTime(item.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Comments Drawer -->
    <CommentDrawer 
      :is-open="isDrawerOpen" 
      :post-id="activePostId" 
      @close="isDrawerOpen = false"
      @comment-added="fetchUserPosts"
    />

    <!-- Edit Profile Modal (Modernized) -->
    <Transition name="modal-fade">
      <div v-if="isEditModalOpen" class="modal-overlay-new" @click.self="isEditModalOpen = false">
        <div class="modal-card-modern">
          <div class="modal-header-modern">
            <h3>Paramètres du profil</h3>
            <button class="close-btn-modern" @click="isEditModalOpen = false">
              <span class="material-symbols-rounded">close</span>
            </button>
          </div>
          
          <form @submit.prevent="handleEditProfile" class="modern-form">
            <div class="photo-edit-section" @click="$refs.profileFileInput.click()">
              <div class="edit-avatar-wrapper">
                <img :src="editPreviewUrl || profileImageUrl" class="edit-avatar-preview" />
                <div class="camera-overlay">
                  <span class="material-symbols-rounded">photo_camera</span>
                </div>
              </div>
              <p class="edit-hint">Changer la photo de profil</p>
            </div>

            <div class="form-grid">
              <div class="modern-input-group">
                <label>Nom</label>
                <div class="input-wrapper">
                  <span class="material-symbols-rounded icon">person</span>
                  <input v-model="editForm.nom" placeholder="Votre nom" required>
                </div>
              </div>
              
              <div class="modern-input-group">
                <label>Région (Cameroun)</label>
                <div class="input-wrapper">
                  <span class="material-symbols-rounded icon">location_on</span>
                  <select v-model="editForm.region" class="modern-select" required>
                    <option value="" disabled>Sélectionnez votre région</option>
                    <option value="Adamaoua">Adamaoua</option>
                    <option value="Centre">Centre</option>
                    <option value="Est">Est</option>
                    <option value="Extrême-Nord">Extrême-Nord</option>
                    <option value="Littoral">Littoral</option>
                    <option value="Nord">Nord</option>
                    <option value="Nord-Ouest">Nord-Ouest</option>
                    <option value="Ouest">Ouest</option>
                    <option value="Sud">Sud</option>
                    <option value="Sud-Ouest">Sud-Ouest</option>
                  </select>
                </div>
              </div>
              
              <div class="modern-input-group full-width">
                <label>Bio</label>
                <textarea v-model="editForm.description" placeholder="Dites-en plus sur vous..." rows="3"></textarea>
              </div>
            </div>
            
            <input ref="profileFileInput" type="file" style="display: none" accept="image/*" @change="handleProfileFileChange">

            <div class="form-footer">
              <button type="button" class="btn btn-ghost" @click="isEditModalOpen = false">Annuler</button>
              <button type="submit" class="btn btn-primary-modern" :disabled="editLoading">
                {{ editLoading ? 'Mise à jour...' : 'Sauvegarder' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import { reactive } from 'vue';
import api, { BASE_URL } from '@/utils/api';
import PostCard from '@/components/PostCard.vue';
import CommentDrawer from '@/components/CommentDrawer.vue';
import Loader from '@/components/Loader.vue';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const user = ref(null);
const posts = ref([]);
const loading = ref(true);

const isDrawerOpen = ref(false);
const activePostId = ref(null);

const activeTab = ref('posts');
const interactions = ref([]);
const interactionsLoading = ref(false);

const isEditModalOpen = ref(false);
const editLoading = ref(false);
const editPreviewUrl = ref(null);
const editForm = reactive({
    nom: '',
    region: '',
    description: '',
    photo: null
});

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
        
        if (!username) {
            console.error('No username found for profile');
            router.push('/login');
            return;
        }

        const userRes = await api.get(`/users/profile/${username}`);
        user.value = userRes.data;

        const postsRes = await api.get(`/users/${user.value.id}/posts`);
        posts.value = postsRes.data;
    } catch (err) {
        console.error('Fetch profile posts error', err);
        if (err.response?.status === 404) {
            Swal.fire('Erreur', 'Utilisateur non trouvé', 'error');
            router.push(`/${authStore.user?.nom}/home`);
        }
    } finally {
        loading.value = false;
    }
};

const fetchInteractions = async () => {
  if (!isMyProfile.value) return;
  interactionsLoading.value = true;
  try {
    const res = await api.get('/user/interactions');
    interactions.value = res.data;
  } catch (err) {
    console.error('Fetch interactions error', err);
  } finally {
    interactionsLoading.value = false;
  }
};

const openEditModal = () => {
    editForm.nom = user.value.nom;
    editForm.region = user.value.region || '';
    editForm.description = user.value.description || '';
    editForm.photo = null;
    editPreviewUrl.value = null;
    isEditModalOpen.value = true;
};

const handleProfileFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        editForm.photo = file;
        editPreviewUrl.value = URL.createObjectURL(file);
    }
};

const handleEditProfile = async () => {
    editLoading.value = true;
    try {
        const formData = new FormData();
        formData.append('nom', editForm.nom);
        formData.append('region', editForm.region);
        formData.append('description', editForm.description);
        if (editForm.photo) {
            formData.append('photo_profil', editForm.photo);
        }

        const res = await api.post('/user/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const oldNom = authStore.user.nom;
        authStore.user = res.data.user;
        localStorage.setItem('user', JSON.stringify(res.data.user));
        user.value = res.data.user;
        isEditModalOpen.value = false;

        Swal.fire({
            title: 'Succès',
            text: 'Profil mis à jour !',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            background: '#fff',
            color: '#1c1e21'
        });

        // Update URL if name changed
        if (editForm.nom !== oldNom) {
            router.push(`/${editForm.nom}/profil`);
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Erreur', err.response?.data?.message || 'Erreur lors de la mise à jour', 'error');
    } finally {
        editLoading.value = false;
    }
};

const handleShareProfile = () => {
    const profileUrl = `${window.location.host}/${user.value.nom}/profil`;
    const message = `Voir mon profil sur !Pozterr : ${profileUrl}`;
    
    Swal.fire({
        title: 'Partager le profil',
        html: `
            <div class="share-options-modern">
                <button id="share-wa" class="btn-share wa">
                   <span class="material-symbols-rounded">chat</span> WhatsApp
                </button>
                <button id="share-copy" class="btn-share copy">
                   <span class="material-symbols-rounded">content_copy</span> Copier le lien
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('share-wa').onclick = () => {
                window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`, '_blank');
                Swal.close();
            };
            document.getElementById('share-copy').onclick = () => {
                navigator.clipboard.writeText(message);
                Swal.fire({ title: 'Copié !', icon: 'success', timer: 1000, showConfirmButton: false });
            };
        }
    });
};

const formatPostImg = (url) => {
    if (!url) return '';
    return url.startsWith('http') ? url : `${BASE_URL}/storage/${url}`;
};

const formatTime = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) + ' à ' + 
           date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const truncate = (str, n) => {
    return (str.length > n) ? str.substr(0, n-1) + '...' : str;
};

const goToPost = (post) => {
    router.push(`/${authStore.user.nom}/home`);
    // Ideally link to specific post but currently home is the main feed
};

onMounted(async () => {
    try {
        if (!authStore.user) {
            await authStore.fetchProfile();
        }
        await fetchUserPosts();
    } catch (err) {
        console.error('Profile onMounted error', err);
        loading.value = false;
    }
});

watch(() => route.params.target_name, fetchUserPosts);
watch(activeTab, (newTab) => {
  if (newTab === 'interactions') fetchInteractions();
});
</script>

<style scoped>
.profile-view {
  padding: 20px 0 100px;
  background: var(--secondary-color);
  min-height: 100vh;
}

.loader-wrapper {
  height: 60vh;
  display: flex;
  justify-content: center;
  align-items: center;
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Redesigned Header Card */
.profile-header-new {
  margin: 0 15px 25px;
  padding: 30px;
  border-radius: 30px;
  background: white;
  box-shadow: 0 10px 30px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.02);
}

.header-main-content {
  display: flex;
  align-items: center;
  gap: 30px;
  margin-bottom: 30px;
}

.avatar-ring {
    padding: 3px;
    background: var(--primary-color);
    border-radius: 50%;
    width: 150px;
    height: 150px;
}

.profile-avatar-large {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid white;
}

.user-info-section {
  flex: 1;
}

.name-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.display-name {
  font-size: 1.8rem;
  font-weight: 800;
  color: #1c1e21;
}

.verified-badge {
  color: var(--primary-color);
  display: flex;
}

.handle {
  color: var(--text-muted);
  font-weight: 500;
  margin-bottom: 20px;
}

.profile-actions {
  display: flex;
  gap: 12px;
}

.btn-edit-modern {
  background: #f0f2f5;
  color: #1c1e21;
  border-radius: 12px;
  padding: 10px 20px;
  font-size: 0.95rem;
}

.btn-primary-modern {
  background: var(--primary-color);
  color: white;
  border-radius: 12px;
  padding: 10px 25px;
}

.btn-icon-modern {
  background: #f0f2f5;
  padding: 10px;
  border-radius: 12px;
}

/* Metrics Section */
.profile-metrics {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  flex: 1;
  background: #f8f9fa;
  padding: 15px;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.2s;
}

/* .metric-card:hover { transform: translateY(-3px); } */

.metric-value {
  font-size: 1.2rem;
  font-weight: 800;
  color: #1c1e21;
}

.metric-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 600;
}

/* Bio Section */
.profile-bio-new {
  border-top: 1px solid #f0f2f5;
  padding-top: 20px;
}

.bio-meta {
  display: flex;
  gap: 20px;
  margin-bottom: 15px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 500;
}

.meta-item span { font-size: 18px; }

.bio-content {
  line-height: 1.6;
  color: #4b4b4b;
}

.bio-content.placeholder {
  font-style: italic;
  color: #b0b0b0;
}

/* Tabs Navigation */
.feed-navigation {
  display: flex;
  gap: 15px;
  margin: 0 15px 20px;
}

.nav-tab {
  flex: 1;
  background: white;
  border: none;
  padding: 15px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-weight: 700;
  color: var(--text-muted);
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.02);
  transition: all 0.2s;
}

.nav-tab.active {
  background: var(--primary-color);
  color: white;
  box-shadow: 0 8px 20px rgba(24, 119, 242, 0.2);
}

/* Section Transitions */
.slide-fade-enter-active, .slide-fade-leave-active {
  transition: all 0.3s ease;
}
.slide-fade-enter-from { transform: translateX(20px); opacity: 0; }
.slide-fade-leave-to { transform: translateX(-20px); opacity: 0; }

/* Empty States */
.empty-state-modern {
  text-align: center;
  padding: 60px 40px;
}

.empty-art {
  font-size: 4rem;
  margin-bottom: 20px;
}

.empty-state-modern h3 {
  margin-bottom: 10px;
  font-weight: 800;
}

.empty-state-modern p {
  color: var(--text-muted);
  margin-bottom: 25px;
}

/* Interactions Timeline */
.interactions-timeline {
  max-width: 600px;
  margin: 0 auto;
  padding: 0 15px;
}

.interaction-node {
  display: flex;
  gap: 20px;
  padding: 20px;
  margin-bottom: 15px;
  border-radius: 20px;
  position: relative;
}

.node-icon {
  width: 45px;
  height: 45px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.node-icon.like { background: #fee2e2; color: #ef4444; }
.node-icon.comment { background: #dbeafe; color: #3b82f6; }

.node-content { flex: 1; }

.node-text { font-size: 0.95rem; margin-bottom: 8px; }

.comment-preview {
  background: #f8f9fa;
  padding: 10px;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 12px;
  border-left: 3px solid #3b82f6;
}

.post-preview-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f1f3f5;
  padding: 10px;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
  word-break: break-all;
}

.post-preview-card:hover { background: #e9ecef; }

.preview-img {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  object-fit: cover;
}

.preview-text { font-size: 0.8rem; color: var(--text-muted); }

.node-time {
  display: block;
  margin-top: 10px;
  font-size: 0.75rem;
  color: var(--text-muted);
}

/* Modern Modal Design */
.modal-overlay-new {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(10px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-card-modern {
  background: white;
  width: 100%;
  max-width: 500px;
  border-radius: 30px;
  padding: 30px;
  box-shadow: 0 30px 60px rgba(0,0,0,0.2);
}

.modal-header-modern {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.modal-header-modern h3 { font-weight: 800; font-size: 1.4rem; }

.close-btn-modern {
  background: #f0f2f5;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
}

.photo-edit-section {
  text-align: center;
  margin-bottom: 30px;
  cursor: pointer;
}

.edit-avatar-wrapper {
  position: relative;
  display: inline-block;
}

.edit-avatar-preview {
  width: 100px;
  height: 100px;
  border-radius: 30px;
  object-fit: cover;
  border: 3px solid white;
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.camera-overlay {
  position: absolute;
  bottom: -5px; right: -5px;
  background: var(--primary-color);
  color: white;
  padding: 8px;
  border-radius: 12px;
}

.edit-hint { margin-top: 10px; font-size: 0.85rem; color: var(--primary-color); font-weight: 600; }

.modern-input-group {
  margin-bottom: 20px;
}

.modern-input-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.input-wrapper {
  display: flex;
  align-items: center;
  background: #f8f9fa;
  border: 2px solid #f1f3f5;
  border-radius: 15px;
  padding: 0 15px;
  transition: all 0.2s;
}

.input-wrapper:focus-within {
  border-color: var(--primary-color);
  background: white;
  box-shadow: 0 0 0 4px rgba(24, 119, 242, 0.1);
}

.input-wrapper .icon { color: var(--text-muted); font-size: 20px; }

.input-wrapper input {
  flex: 1;
  border: none;
  background: none;
  padding: 12px;
  font-weight: 500;
}

.input-wrapper select {
  flex: 1;
  border: none;
  background: none;
  padding: 12px;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
}

.input-wrapper select:focus { outline: none; }

.modern-form textarea {
  width: 100%;
  background: #f8f9fa;
  border: 2px solid #f1f3f5;
  border-radius: 15px;
  padding: 15px;
  font-family: inherit;
  resize: none;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 20px;
}

/* Share Modal Custom */
.share-options-modern {
    display: grid;
    gap: 12px;
    padding: 15px 0;
}

.btn-share {
    border: none;
    padding: 15px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 700;
    cursor: pointer;
    color: white;
}

.btn-share.wa { background: #25D366; }
.btn-share.copy { background: var(--primary-color); }

@media (min-width: 768px) {
  .profile-container {
    max-width: 700px;
    margin: 0 auto;
  }
}

@media (max-width: 600px) {
  .header-main-content {
    flex-direction: column;
    text-align: center;
    gap: 15px;
  }
  .profile-metrics { gap: 10px; }
  .metric-card { padding: 10px; }
}
</style>
