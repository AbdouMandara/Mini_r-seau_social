<template>
  <div class="post-card card" @mouseenter="showCounts = true" @mouseleave="showCounts = false">
    <!-- Header -->
    <div class="post-header">
      <img :src="userAvatar" class="post-avatar" @error="handleAvatarError" />
      <div class="user-info">
        <div class="name-with-badges">
          <h3 class="author-name" @click="goToProfile">{{ authorNom }}</h3>
          <div v-if="userBadges.length > 0" class="user-badges">
            <span 
              v-for="badge in userBadges" 
              :key="badge.id_badge" 
              class="badge-icon material-symbols-rounded"
              :title="badge.name"
              :style="{ color: badge.color }"
            >{{ badge.icon }}</span>
          </div>
        </div>
        <p class="joined-date">Publié le {{ formatDate(post.created_at) }}</p>
      </div>
      <div v-if="shouldShowActions" class="post-actions">
        <span class="material-symbols-rounded menu-dots" @click="showMenu = !showMenu">more_horiz</span>
        <div v-if="showMenu" class="dropdown-menu">
          <button v-if="isOwner" @click="editPost">Modifier</button>
          <button v-if="isOwner" @click="deletePost" class="delete-btn">Supprimer</button>
          <button v-if="!isOwner" @click="reportPost" class="report-btn">Signaler</button>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="post-content">
      <div class="post-tags">
        <span 
          v-if="post.tag" 
          class="tag-badge clickable" 
          :class="post.tag"
          @click="filterByTag(post.tag)"
        >#{{ post.tag }}</span>
      </div>
      
      <div v-if="post.img_post" class="post-image-container">
        <img :src="postImageUrl" class="post-image" />
      </div>
      <p class="post-description" v-html="formattedDescription"></p>
    </div>

    <!-- Footer -->
    <div class="post-footer">
      <button class="action-btn" :class="{ 'liked': isLiked }" @click="handleLike">
        <span class="material-symbols-rounded icon">{{ isLiked ? 'favorite' : 'favorite' }}</span>
        <span class="count visible">{{ likesCount }}</span>
      </button>

      <button class="action-btn" :disabled="!post.allow_comments" @click="$emit('open-comments', post)">
        <span class="material-symbols-rounded icon">chat_bubble</span>
        <span class="count visible">{{ commentsCount }}</span>
      </button>

      <button class="action-btn" @click="handleShare">
        <span class="material-symbols-rounded icon">share</span>
      </button>
        
    <ReportModal 
      :is-open="showReportModal"
      :post-id="post.id_post"
      :user-id="post.user?.id || post.user?.data?.id"
      @close="showReportModal = false"
    />
  </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute, useRouter } from 'vue-router';
import confetti from 'canvas-confetti';
import api, { BASE_URL } from '@/utils/api';
import Swal from 'sweetalert2';
import ReportModal from './ReportModal.vue';

const props = defineProps(['post']);
const emit = defineEmits(['open-comments', 'refresh']);

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const showMenu = ref(false);
const showCounts = ref(false);
const isLiked = ref(props.post.likes?.some(l => l.id_user === authStore.user?.id));
const likesCount = ref(props.post.likes?.length || 0);
const commentsCount = ref(props.post.comments?.length || 0);

const isOnProfile = computed(() => route.name === 'profile');
const shouldShowActions = computed(() => (isOwner.value && isOnProfile.value) || !isOwner.value); // Allow reporting for others

const goToProfile = () => {
    const targetNom = props.post.user?.nom || props.post.user?.data?.nom || '';
    if (targetNom) {
        router.push({ 
            name: 'profile', 
            params: { 
                nom_user: authStore.user?.nom || 'user', 
                target_name: (props.post.user?.slug || targetNom).replace(/ /g, '_')
            } 
        });
    }
};

const isOwner = computed(() => {
    const authId = authStore.user?.id || authStore.user?.data?.id;
    const postUserId = props.post.user?.id || props.post.user?.data?.id || props.post.id_user;
    return authId === postUserId;
});

const showReportModal = ref(false);

const reportPost = () => {
  showMenu.value = false;
  showReportModal.value = true;
};

const filterByTag = (tag) => {
    router.push({ 
        name: 'home', 
        params: { nom_user: authStore.user?.nom || 'user' },
        query: { tag } 
    });
};

const formattedDescription = computed(() => {
    if (!props.post.description) return '';
    
    // Replace mentions @Name with links
    // JS compatible regex for names with potential spaces (covers accented chars)
    return props.post.description.replace(/@([a-zA-Z0-9_\u00C0-\u017F]+(?:\s[a-zA-Z0-9_\u00C0-\u017F]+)*)/g, (match, name) => {
        const currentUser = authStore.user?.nom || 'user';
        return `<a href="/${currentUser}/profil/${name.trim()}" class="mention-link">${match}</a>`;
    });
});

// Sync local refs when props change (from parent refreshes)
watch(() => props.post, (newPost) => {
    isLiked.value = newPost.likes?.some(l => l.id_user === authStore.user?.id);
    likesCount.value = newPost.likes?.length || 0;
    commentsCount.value = newPost.comments?.length || 0;
}, { deep: true });

const userBadges = computed(() => {
    const user = props.post.user?.data || props.post.user;
    return user?.badges || [];
});

const authorNom = computed(() => {
    const user = props.post.user?.data || props.post.user;
    return user?.nom || 'Utilisateur inconnu';
});

const userAvatar = computed(() => {
  const user = props.post.user?.data || props.post.user;
  const url = user?.photo_profil;
  if (url) {
    if (url.startsWith('http')) return url;
    return `${BASE_URL}/storage/${url}`;
  }
  return 'https://ui-avatars.com/api/?name=' + (user?.nom || 'User');
});

const handleAvatarError = (e) => {
  const user = props.post.user?.data || props.post.user;
  e.target.src = 'https://ui-avatars.com/api/?name=' + (user?.nom || 'User');
};

const postImageUrl = computed(() => {
  const url = props.post.img_post;
  if (!url) return '';
  return url.startsWith('http') ? url : `${BASE_URL}/storage/${url}`;
});

const formatDate = (dateString) => {
  if (!dateString) return '...';
  // Handle Laravel date objects or strings
  const str = typeof dateString === 'object' ? dateString.date : dateString;
  const date = new Date(str);
  
  if (isNaN(date.getTime())) {
    return '...';
  }
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const handleLike = async () => {
  // Optimistic Update
  const originalLiked = isLiked.value;
  const originalCount = likesCount.value;

  isLiked.value = !isLiked.value;
  likesCount.value = isLiked.value ? likesCount.value + 1 : likesCount.value - 1;

  if (isLiked.value) {
    confetti({
      particleCount: 80,
      spread: 50,
      origin: { y: 0.6 },
      ticks: 150
    });
  }

  try {
    const res = await api.post(`/posts/${props.post.id_post}/like`);
    // Sync with server response
    isLiked.value = res.data.liked;
    likesCount.value = res.data.likes_count;
  } catch (err) {
    console.error('Like error', err);
    // Rollback on error
    isLiked.value = originalLiked;
    likesCount.value = originalCount;
    
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'Erreur lors du like',
      showConfirmButton: false,
      timer: 3000
    });
  }
};

const handleShare = () => {
    const userNom = props.post.user?.nom || 'Utilisateur';
    const profileUrl = `${window.location.origin}/${userNom}/profil/${authStore.user?.nom}`;
    const shareUrl = `${window.location.origin}/post/${props.post.id_post}`;
    const message = `Regarde cette publication de ${userNom} sur !Pozterr : ${shareUrl}\n\nRetrouve aussi son profil ici : ${profileUrl}`;
    
    Swal.fire({
        title: 'Partager le post',
        html: `
            <div style="display: flex; flex-direction: column; gap: 12px; padding-top: 10px;">
                <button id="share-wa" class="swal2-confirm swal2-styled" style="background: #25D366; border-radius: 12px; margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                   <span class="material-symbols-rounded">chat</span> WhatsApp
                </button>
                <button id="share-copy" class="swal2-confirm swal2-styled" style="background: var(--primary-color); border-radius: 12px; margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                   <span class="material-symbols-rounded">content_copy</span> Copier le lien
                </button>
            </div>
        `,
        showConfirmButton: false,
        background: 'var(--card-bg)',
        color: 'var(--text-color)',
        didOpen: () => {
            document.getElementById('share-wa').onclick = () => {
                window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`, '_blank');
                Swal.close();
            };
            document.getElementById('share-copy').onclick = () => {
                navigator.clipboard.writeText(message);
                Swal.fire({ 
                    title: 'Lien copié !', 
                    icon: 'success', 
                    timer: 1000, 
                    showConfirmButton: false,
                    background: 'var(--card-bg)',
                    color: 'var(--text-color)'
                });
            };
        }
    });
};

const deletePost = async () => {
  const result = await Swal.fire({
    title: 'Supprimer ce post ?',
    text: "Cette action est irréversible !",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#1877f2',
    confirmButtonText: 'Oui, supprimer',
    cancelButtonText: 'Annuler'
  });

  if (result.isConfirmed) {
    try {
      await api.delete(`/posts/${props.post.id_post}`);
      Swal.fire({
        title: 'Supprimé !',
        text: 'Votre post a été supprimé.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      });
      emit('refresh');
    } catch (err) {
      console.error(err);
      Swal.fire({
        title: 'Erreur',
        text: 'Une erreur est survenue.',
        icon: 'error'
      });
    }
  }
};

const editPost = () => {
  showMenu.value = false;
  router.push(`/${authStore.user.nom}/edit_post/${props.post.id_post}`);
};
</script>

<style scoped>
.post-card {
  margin-bottom: 20px;
  overflow: visible;
}

.post-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  position: relative;
}

.post-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.user-info h3 {
  font-size: 1rem;
  margin: 0;
}

.joined-date {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.post-image-container {
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border-radius: 8px;
    margin-bottom: 12px;
}

.post-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.name-with-badges {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-badges {
    display: flex;
    gap: 4px;
}

.badge-icon {
    font-size: 1rem;
    cursor: help;
}

.author-name {
    cursor: pointer;
    transition: color 0.2s;
}

.author-name:hover {
    color: var(--primary-color);
}

.post-description {
  font-size: 1rem;
  margin-bottom: 15px;
  word-break: break-all;
}

.post-footer {
  display: flex;
  justify-content: space-around;
  border-top: 1px solid var(--border-color);
  padding-top: 10px;
}

.action-btn {
  background: none;
  border: none;
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--text-muted);
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: background 0.2s;
}

.action-btn:hover:not(:disabled) {
  background: var(--secondary-color);
}

.count {
    opacity: 1;
    font-size: 0.85rem;
    font-weight: 700;
}

/* Post Tags Styles */
.post-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.tag-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    background: var(--input-bg);
    color: var(--primary-color);
}

.tag-badge.etude { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.tag-badge.divertissement { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
.tag-badge.info { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.tag-badge.programmation { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.tag-badge.maths { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.tag-badge.devoir { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.info-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--secondary-color);
    color: var(--text-muted);
}



.tag-badge.clickable {
    cursor: pointer;
    transition: transform 0.2s, background 0.2s;
}

:deep(.mention-link) {
    color: var(--primary-color);
    font-weight: 700;
    text-decoration: none;
    transition: opacity 0.2s;
}

:deep(.mention-link:hover) {
    opacity: 0.8;
}

.action-btn.liked .material-symbols-rounded {
  color: var(--error);
  font-variation-settings: 'FILL' 1;
}

.action-btn.liked .count {
  color: var(--error);
}

.menu-dots {
    font-size: 24px;
    color: var(--text-muted);
}

.action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.post-actions {
  margin-left: auto;
  cursor: pointer;
  padding: 0 10px;
}

.dropdown-menu {
  position: absolute;
  right: 0;
  top: 35px;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 6px;
  z-index: 10;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  min-width: 140px;
}

.dark .dropdown-menu {
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.dropdown-menu button {
  display: block;
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  border-radius: 8px;
  color: var(--text-color);
  font-size: 0.9rem;
  font-weight: 500;
}

.dropdown-menu button:hover {
  background: var(--secondary-color);
}

.delete-btn {
  color: var(--error) !important;
}

.report-btn {
  color: var(--text-color);
}

.report-btn:hover {
  color: #f59e0b;
}

@media (max-width: 480px) {
  .post-card {
    margin-bottom: 12px;
    border-radius: 12px;
  }
  
  .post-header {
    gap: 8px;
    margin-bottom: 8px;
  }
  
  .post-avatar {
    width: 36px;
    height: 36px;
  }
  
  .user-info h3 {
    font-size: 0.9rem;
  }
  
  .post-description {
    font-size: 0.9rem;
    margin-bottom: 10px;
  }
  
  .action-btn {
    padding: 6px;
    font-size: 0.85rem;
  }
  
  .count {
    font-size: 0.75rem;
  }

  .tag-badge {
    padding: 3px 8px;
    font-size: 0.7rem;
  }
}
</style>