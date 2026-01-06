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
            <div v-if="user.badges?.length > 0" class="profile-badges-row">
              <span 
                v-for="badge in user.badges" 
                :key="badge.id_badge" 
                class="profile-badge-item"
                :title="badge.description"
                :style="{ background: badge.color + '15', color: badge.color, border: '1px solid ' + badge.color + '30' }"
              >
                <span class="badge-icon-mini">{{ badge.icon }}</span>
                {{ badge.name }}
              </span>
            </div>
            <p class="handle">@{{ (user.slug || user.nom).toLowerCase().replace(/ /g, '_') }}</p>
            
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
                <button 
                  class="btn" 
                  :class="isFollowing ? 'btn-secondary-modern' : 'btn-primary-modern'"
                  @click="toggleFollow"
                  :disabled="followLoading"
                >
                  {{ isFollowing ? 'Suivi' : 'Suivre' }}
                </button>
                <div class="secondary-actions">
                    <button class="btn btn-icon-modern" @click="handleShareProfile">
                       <span class="material-symbols-rounded">share</span>
                    </button>
                    <!-- Only show report button if not admin, or admins can report too -->
                    <button class="btn btn-icon-modern report-btn" @click="openReportModal" title="Signaler l'utilisateur">
                       <span class="material-symbols-rounded">flag</span>
                    </button>
                </div>
              </template>
            </div>
          </div>
        </div>

    <ReportModal 
      :is-open="showReportModal"
      :user-id="user?.id"
      :user-name="user?.nom"
      @close="showReportModal = false"
    />

        <div class="profile-metrics">
          <div class="metric-card">
            <span class="metric-value">{{ posts.length }}</span>
            <span class="metric-label">Posts</span>
          </div>
          <div class="metric-card clickable" @click="openUserList('followers')">
            <span class="metric-value">{{ user.followers_count || 0 }}</span>
            <span class="metric-label">Abonnés</span>
          </div>
          <div class="metric-card clickable" @click="openUserList('following')">
            <span class="metric-value">{{ user.following_count || 0 }}</span>
            <span class="metric-label">Abonnements</span>
          </div>
        </div>

        <div class="profile-bio-new">
          <div class="bio-meta-grid">
            <span class="meta-item">
              <span class="material-symbols-rounded">school</span>
              {{ user.etablissement || 'Étudiant' }}
            </span>
            <span class="meta-item">
              <span class="material-symbols-rounded">category</span>
              {{ user.filiere }} (Niv. {{ user.niveau }})
            </span>
            <span class="meta-item">
              <span class="material-symbols-rounded">calendar_today</span>
              Depuis {{ new Date(user.created_at).getFullYear() }}
            </span>
          </div>
          <p v-if="user.bio" class="bio-content">{{ user.bio }}</p>
          <p v-else-if="isMyProfile" class="bio-content placeholder">Ajoutez une bio pour vous présenter au monde...</p>
        </div>
      </div>

      <!-- Feed Tabs Navigation -->
      <!-- Feed Tabs Navigation -->
      <div class="feed-navigation">
        <button class="nav-tab" :class="{ active: activeTab === 'posts' }" @click="activeTab = 'posts'" :style="!isMyProfile ? 'flex: 0 0 auto; width: 100%;' : ''">
          <span class="material-symbols-rounded">grid_view</span>
          Publications
        </button>
        <button v-if="isMyProfile" class="nav-tab" :class="{ active: activeTab === 'interactions' }" @click="activeTab = 'interactions'">
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
              <h3 v-if="isMyProfile">Pas encore de posts</h3>
              <h3 v-else>Aucune publication</h3>
              
              <p v-if="isMyProfile">Commencez à partager vos moments avec la communauté.</p>
              <p v-else>Ce utilisateur n'a encore rien partagé.</p>
              
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

    <!-- User List Modal (Followers/Following) -->
    <Transition name="modal-fade">
      <div v-if="showUserListModal" class="modal-overlay-new" @click.self="closeUserList">
        <div class="modal-card-modern user-list-modal">
          <div class="modal-header-modern">
            <h3>{{ userListType === 'followers' ? 'Abonnés' : 'Abonnements' }}</h3>
            <button class="close-btn-modern" @click="closeUserList">
              <span class="material-symbols-rounded">close</span>
            </button>
          </div>
          
          <div class="user-list-content">
             <div v-if="userListLoading" class="mini-loader">
                <Loader />
             </div>
             <div v-else-if="userList.length === 0" class="empty-list">
                <span style="font-size: 3rem; margin-bottom: 10px;">👥</span>
                <p>Aucun utilisateur trouvé.</p>
             </div>
             <div v-else class="user-list-items">
                <div v-for="u in userList" :key="u.id" class="user-list-item">
                   <div class="user-info-group" @click="navigateToUser(u)">
                      <img :src="u.photo_profil ? (u.photo_profil.startsWith('http') ? u.photo_profil : `${BASE_URL}/storage/${u.photo_profil}`) : 'https://ui-avatars.com/api/?name=' + u.nom" class="user-list-avatar" />
                      <div class="user-text-content">
                        <span class="user-list-name">{{ u.nom }}</span>
                        <span class="user-list-handle">@{{ (u.slug || u.nom).toLowerCase().replace(/ /g, '_') }}</span>
                      </div>
                   </div>
                   
                   <button 
                      v-if="u.id !== authStore.user.id"
                      class="btn-follow-action" 
                      :class="u.is_following ? 'following' : 'start-follow'"
                      @click.stop="toggleFollowUserInList(u)"
                   >
                      {{ u.is_following ? 'Suivi' : 'Suivre' }}
                   </button>
                </div>
             </div>
          </div>
        </div>
      </div>
    </Transition>

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
                <label>Établissement</label>
                <div class="input-wrapper">
                  <span class="material-symbols-rounded icon">school</span>
                  <input v-model="editForm.etablissement" placeholder="Ex: Université de Yaoundé I" required>
                </div>
              </div>

              <div class="modern-input-group">
                <label>Filière</label>
                <div class="input-wrapper">
                  <span class="material-symbols-rounded icon">category</span>
                  <select v-model="editForm.filiere" class="modern-select" required>
                    <option value="GL">GL</option>
                    <option value="GLT">GLT</option>
                    <option value="SWE">SWE</option>
                    <option value="MVC">MVC</option>
                    <option value="LTM">LTM</option>
                  </select>
                </div>
              </div>

              <div class="modern-input-group">
                <label>Niveau</label>
                <div class="input-wrapper">
                  <span class="material-symbols-rounded icon">star</span>
                  <select v-model="editForm.niveau" class="modern-select" required>
                    <option value="1">Niveau 1</option>
                    <option value="2">Niveau 2</option>
                  </select>
                </div>
              </div>
              
              <div class="modern-input-group full-width">
                <label>Bio</label>
                <textarea v-model="editForm.bio" placeholder="Dites-en plus sur vous..." rows="3"></textarea>
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
import ReportModal from '@/components/ReportModal.vue';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const user = ref(null);
const posts = ref([]);
const loading = ref(true);

const showReportModal = ref(false);
const openReportModal = () => showReportModal.value = true;

const isDrawerOpen = ref(false);
const activePostId = ref(null);

const activeTab = ref('posts');
const interactions = ref([]);
const interactionsLoading = ref(false);

const isFollowing = ref(false);
const followLoading = ref(false);

const isEditModalOpen = ref(false);
const editLoading = ref(false);
const editPreviewUrl = ref(null);
const editForm = reactive({
    nom: '',
    etablissement: '',
    filiere: '',
    niveau: '',
    bio: '',
    photo: null
});

// User List Modal State
const showUserListModal = ref(false);
const userListType = ref('followers'); // 'followers' or 'following'
const userList = ref([]);
const userListLoading = ref(false);

const openUserList = async (type) => {
    userListType.value = type;
    showUserListModal.value = true;
    userListLoading.value = true;
    userList.value = [];
    
    try {
        const endpoint = type === 'followers' ? `/users/${user.value.id}/followers` : `/users/${user.value.id}/following`;
        const res = await api.get(endpoint);
        userList.value = res.data;
    } catch (err) {
        console.error('Fetch user list error', err);
        Swal.fire('Erreur', 'Impossible de charger la liste', 'error');
    } finally {
        userListLoading.value = false;
    }
};

const closeUserList = () => {
    showUserListModal.value = false;
};

const navigateToUser = (u) => {
    closeUserList();
    const currentSlug = (authStore.user.slug || authStore.user.nom).replace(/ /g, '_');
    const targetSlug = (u.slug || u.nom).replace(/ /g, '_');
    router.push(`/${currentSlug}/profil/${targetSlug}`);
};

const toggleFollowUserInList = async (u) => {
    // Optimistic UI update
    const originalState = u.is_following;
    u.is_following = !originalState;
    
    try {
        if (originalState) {
            // Unfollow
            await api.delete(`/users/${u.id}/unfollow`);
            // If viewing my own 'following' list, I might want to remove them from the list?
            // User requested "voir mon nombre d'abonnements et le bouton suivi". 
            // Usually we don't remove immediately to avoid UI jumping, unless requested.
            // But we should update the counters on the profile page if it's my profile.
        } else {
            // Follow
            await api.post(`/users/${u.id}/follow`);
        }
        
        // Update main profile counts if it affects them
        // If I am following/unfollowing someone, my "following_count" changes if I'm on my own profile.
        if (isMyProfile.value) {
            // Refresh profile counts
             const res = await api.get(`/users/profile/${user.value.nom}`);
             user.value.following_count = res.data.following_count;
             user.value.followers_count = res.data.followers_count; // In case of follow back
        }

    } catch (err) {
        console.error('Toggle follow error', err);
        u.is_following = originalState; // Revert on error
    }
};

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
        user.value = userRes.data.data || userRes.data;
        isFollowing.value = user.value.is_following;

        const postsRes = await api.get(`/users/${user.value.id}/posts`);
        posts.value = postsRes.data.data || postsRes.data;
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

const toggleFollow = async (event) => {
    if (followLoading.value) return;
    followLoading.value = true;
    try {
        if (isFollowing.value) {
            const res = await api.delete(`/users/${user.value.id}/unfollow`);
            user.value.followers_count = res.data.follower_count;
            user.value.following_count = res.data.following_count;
            isFollowing.value = false;
        } else {
            // Optimistic animation start
            triggerStarRain(event.target);
            
            const res = await api.post(`/users/${user.value.id}/follow`);
            user.value.followers_count = res.data.follower_count;
            user.value.following_count = res.data.following_count;
            isFollowing.value = true;
        }
    } catch (err) {
        console.error('Follow error', err);
        Swal.fire('Erreur', 'Impossible de modifier le statut de suivi', 'error');
    } finally {
        followLoading.value = false;
    }
};

const triggerStarRain = (element) => {
    const rect = element.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    for (let i = 0; i < 20; i++) {
        const star = document.createElement('div');
        star.classList.add('star-particle');
        star.innerHTML = '⭐';
        document.body.appendChild(star);

        const angle = Math.random() * Math.PI * 2;
        const velocity = 2 + Math.random() * 3;
        const tx = Math.cos(angle) * (50 + Math.random() * 50);
        const ty = Math.sin(angle) * (50 + Math.random() * 50);

        star.style.left = `${centerX}px`;
        star.style.top = `${centerY}px`;
        star.style.setProperty('--tx', `${tx}px`);
        star.style.setProperty('--ty', `${ty}px`);

        // Random animation duration
        star.style.animation = `star-explosion 1s ease-out forwards`;
        
        // Remove after animation
        setTimeout(() => {
            star.remove();
        }, 1000);
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
    editForm.etablissement = user.value.etablissement || '';
    editForm.filiere = user.value.filiere || 'GL';
    editForm.niveau = user.value.niveau || '1';
    editForm.bio = user.value.bio || '';
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
        formData.append('etablissement', editForm.etablissement);
        formData.append('filiere', editForm.filiere);
        formData.append('niveau', editForm.niveau);
        formData.append('bio', editForm.bio);
        if (editForm.photo) {
            formData.append('photo_profil', editForm.photo);
        }

        const res = await api.post('/user/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const oldNom = authStore.user.nom;
        authStore.user = res.data.user;
        user.value = res.data.user;
        isEditModalOpen.value = false;

        Swal.fire({
            title: 'Succès',
            text: 'Profil mis à jour !',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            background: 'var(--card-bg)',
            color: 'var(--text-color)'
        });

        // Update URL if name changed
        if (editForm.nom !== oldNom) {
            router.push(`/${editForm.nom.replace(/ /g, '_')}/profil`);
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Erreur', err.response?.data?.message || 'Erreur lors de la mise à jour', 'error');
    } finally {
        editLoading.value = false;
    }
};

const handleShareProfile = () => {
    const userNom = user.value.nom || user.value.data?.nom || 'User';
    const username = (user.value.slug || user.value.data?.slug || userNom).replace(/ /g, '_');
    const profileUrl = `${window.location.origin}/${username}/profil`;
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
    if (!dateStr) return '...';
    const str = typeof dateStr === 'object' ? dateStr.date : dateStr;
    const date = new Date(str);
    if (isNaN(date.getTime())) return '...';
    
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) + ' à ' + 
           date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const truncate = (str, n) => {
    return (str.length > n) ? str.substr(0, n-1) + '...' : str;
};

const goToPost = (post) => {
    router.push({ 
        name: 'home', 
        params: { 
            nom_user: authStore.user?.nom || 'user',
            post_id: post.id_post 
        } 
    });
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

// Watch modals to lock body scroll
watch([isEditModalOpen, showUserListModal], ([editOpen, listOpen]) => {
    if (editOpen || listOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
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
  background: var(--card-bg);
  border: 1px solid var(--border-color);
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
  border: 4px solid var(--card-bg);
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
  color: var(--text-color);
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

.profile-badges-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 5px 0 15px;
}

.profile-badge-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge-icon-mini {
  font-size: 1rem;
}

.profile-actions {
  display: flex;
  gap: 12px;
}

.btn-edit-modern {
  background: var(--input-bg);
  color: var(--text-color);
  border-radius: 12px;
  padding: 10px 20px;
  font-size: 0.95rem;
}

.secondary-actions {
    display: flex;
    gap: 8px;
}

.report-btn {
    color: var(--text-muted);
}
.report-btn:hover {
    color: var(--error);
    background: rgba(239, 68, 68, 0.1);
}

.btn-primary-modern {
  background: var(--primary-color);
  color: white;
  border-radius: 12px;
  padding: 10px 25px;
}

.btn-icon-modern {
  background: var(--input-bg);
  padding: 10px;
  border-radius: 12px;
  color: var(--text-color);
}

/* Metrics Section */
.profile-metrics {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  flex: 1;
  background: var(--input-bg);
  padding: 15px;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: all 0.2s;
  color: var(--text-color);
}

/* .metric-card:hover { transform: translateY(-3px); } */

.metric-value {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--text-color);
}

.metric-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 600;
}

/* Bio Section */
.profile-bio-new {
  border-top: 1px solid var(--border-color);
  padding-top: 20px;
}

.bio-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
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
  color: var(--text-color);
}

.bio-content.placeholder {
  font-style: italic;
  color: var(--text-muted);
}

/* Tabs Navigation */
.feed-navigation {
  display: flex;
  gap: 15px;
  margin: 0 15px 20px;
}

.nav-tab {
  flex: 1;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  padding: 15px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-weight: 700;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s;
}

.nav-tab.active {
  background: var(--primary-color);
  color: white;
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

.node-icon.like { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.node-icon.comment { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

.node-content { flex: 1; }

.node-text { font-size: 0.95rem; margin-bottom: 8px; }

.comment-preview {
  background: var(--secondary-color);
  padding: 10px;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 12px;
  border-left: 3px solid #3b82f6;
  color: var(--text-color);
}

.post-preview-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--input-bg);
  padding: 10px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  word-break: break-all;
}

.post-preview-card:hover { background: var(--secondary-color); opacity: 0.8; }

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

/* Star Rain Animation */
:global(.star-particle) {
    position: fixed;
    font-size: 1.2rem;
    pointer-events: none;
    z-index: 9999;
}

@keyframes star-explosion {
    0% {
        opacity: 1;
        transform: translate(0, 0) scale(0.5);
    }
    100% {
        opacity: 0;
        transform: translate(var(--tx), var(--ty)) scale(1.5);
    }
}

.modal-card-modern {
  background: var(--card-bg);
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  border-radius: 20px;
  overflow-y: auto;
  border: 1px solid var(--border-color);
  animation: slideUp 0.3s ease-out;
  scrollbar-width: thin;
}

/* User List Modal Redesign */
.user-list-modal {
    width: 100%;
    max-width: 500px; 
    height: 70vh;    
    max-height: 700px;
    display: flex;
    flex-direction: column;
    background: var(--card-bg);
    border-radius: 24px;
    overflow: hidden; 
}

.modal-header-modern {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: var(--card-bg);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 10;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 0;
}

.modal-header-modern h3 { 
  font-weight: 800; 
  font-size: 1.25rem; 
  margin: 0;
}

.user-list-content {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    /* Smooth scrolling */
    scrollbar-width: thin;
    scrollbar-color: var(--input-bg) transparent;
}

.user-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px; 
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.user-list-item:hover {
    background: var(--input-bg); /* Subtle background change only */
}

.user-info-group {
    display: flex;
    align-items: center;
    gap: 16px;
    cursor: pointer;
    flex: 1;
    min-width: 0; /* For truncation */
}

.user-list-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid rgba(0,0,0,0.05);
}

.user-text-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0; /* For text text-overflow */
}

.user-list-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--text-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-list-handle {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.btn-follow-action {
    padding: 8px 20px;
    border-radius: 9999px; /* Pill shape */
    font-size: 0.9rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn-follow-action.start-follow {
    background: var(--primary-color);
    color: white;
}

.btn-follow-action.start-follow:hover {
    filter: brightness(1.1);
}

.btn-follow-action.following {
    background: var(--secondary-color);
    color: var(--text-color);
}

.btn-follow-action.following:hover {
    background: var(--input-bg);
}

.empty-list {
    padding: 60px 30px;
    text-align: center;
    color: var(--text-muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.clickable {
    cursor: pointer;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header-modern {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.modal-header-modern h3 { font-weight: 800; font-size: 1.4rem; }

.close-btn-modern {
  background: var(--input-bg);
  color: var(--text-color);
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
  border: 3px solid var(--card-bg);
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
  background: var(--input-bg);
  border: 2px solid var(--border-color);
  border-radius: 15px;
  padding: 0 15px;
  transition: all 0.2;
}

.input-wrapper:focus-within {
  border-color: var(--primary-color);
  background: var(--card-bg);
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

.modern-form {
  padding: 20px;
}

.modern-form textarea {
  width: 100%;
  background: var(--input-bg);
  border: 2px solid var(--border-color);
  border-radius: 15px;
  padding: 15px;
  font-family: inherit;
  resize: none;
  color: var(--text-color);
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 20px;
}

/* Share Modal Custom */


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

<style>
/* Global styles for SweetAlert content */
.share-options-modern {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.btn-share {
    border: none;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: transform 0.2s;
    font-size: 0.95rem;
}



.btn-share.wa { 
    background: #25D366; 
    color: white; 
}

.btn-share.copy { 
    background: var(--input-bg); 
    color: var(--text-color); 
}
</style>
