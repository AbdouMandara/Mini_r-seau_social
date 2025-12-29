<template>
  <div class="post-card card">
    <!-- Header -->
    <div class="post-header">
      <img :src="userAvatar" class="post-avatar" @error="handleAvatarError" />
      <div class="user-info">
        <h3 class="author-name" @click="goToProfile">{{ post.user.nom }}</h3>
        <p class="joined-date">a rejoint le {{ formatDate(post.user.created_at) }}</p>
      </div>
      <div v-if="shouldShowActions" class="post-actions">
        <span class="material-symbols-rounded menu-dots" @click="showMenu = !showMenu">more_horiz</span>
        <div v-if="showMenu" class="dropdown-menu">
          <button @click="editPost">Modifier</button>
          <button @click="deletePost" class="delete-btn">Supprimer</button>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="post-content">
      <div v-if="post.img_post" class="post-image-container">
        <img :src="postImageUrl" class="post-image" />
      </div>
      <p class="post-description">{{ post.description }}</p>
    </div>

    <!-- Footer -->
    <div class="post-footer">
      <button class="action-btn" :class="{ 'liked': isLiked }" @click="handleLike">
        <span class="material-symbols-rounded icon">{{ isLiked ? 'favorite' : 'favorite' }}</span>
        <span class="count">{{ likesCount }}</span>
      </button>

      <button class="action-btn" :disabled="!post.allow_comments" @click="$emit('open-comments', post)">
        <span class="material-symbols-rounded icon">chat_bubble</span>
        <span class="count">{{ commentsCount }}</span>
      </button>

      <button class="action-btn" @click="handleShare">
        <span class="material-symbols-rounded icon">share</span>
      </button>
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

const props = defineProps(['post']);
const emit = defineEmits(['open-comments', 'refresh']);

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const showMenu = ref(false);
const isLiked = ref(props.post.likes?.some(l => l.id_user === authStore.user?.id));
const likesCount = ref(props.post.likes?.length || 0);
const commentsCount = ref(props.post.comments?.length || 0);

const isOwner = computed(() => authStore.user?.id === props.post.id_user);
const isOnProfile = computed(() => route.name === 'profile');
const shouldShowActions = computed(() => isOwner.value && isOnProfile.value);

const goToProfile = () => {
    // Navigate correctly based on current route or target
    const targetNom = props.post.user?.nom;
    router.push(`/${authStore.user?.nom}/profil/${targetNom}`);
};

// Sync local refs when props change (from parent refreshes)
watch(() => props.post, (newPost) => {
    isLiked.value = newPost.likes?.some(l => l.id_user === authStore.user?.id);
    likesCount.value = newPost.likes?.length || 0;
    commentsCount.value = newPost.comments?.length || 0;
}, { deep: true });

const userAvatar = computed(() => {
  const url = props.post.user?.photo_profil;
  if (!url) return 'https://ui-avatars.com/api/?name=' + props.post.user?.nom;
  return url.startsWith('http') ? url : `${BASE_URL}/storage/${url}`;
});

const handleAvatarError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=' + props.post.user?.nom;
};

const postImageUrl = computed(() => {
  const url = props.post.img_post;
  if (!url) return '';
  return url.startsWith('http') ? url : `${BASE_URL}/storage/${url}`;
});

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

const handleLike = async () => {
  try {
    const res = await api.post(`/posts/${props.post.id_post}/like`);
    isLiked.value = res.data.liked;
    likesCount.value = res.data.likes_count;
    
    if (res.data.liked) {
      confetti({
        particleCount: 80,
        spread: 50,
        origin: { y: 0.6 },
        ticks: 150 // Durée réduite (ticks est le nombre de frames)
      });
    }
  } catch (err) {
    console.error('Like error', err);
  }
};

const handleShare = () => {
    const profileUrl = `${window.location.origin}/${props.post.user.nom}/profil/${authStore.user?.nom}`;
    const shareUrl = `${window.location.origin}/post/${props.post.id_post}`;
    const message = `Regarde cette publication de ${props.post.user.nom} sur !pozterr : ${shareUrl}\n\nRetrouve aussi son profil ici : ${profileUrl}`;
    
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
        didOpen: () => {
            document.getElementById('share-wa').onclick = () => {
                window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`, '_blank');
                Swal.close();
            };
            document.getElementById('share-copy').onclick = () => {
                navigator.clipboard.writeText(message);
                Swal.fire({ title: 'Lien copié !', icon: 'success', timer: 1000, showConfirmButton: false });
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
}

.post-footer {
  display: flex;
  justify-content: space-around;
  border-top: 1px solid #eee;
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
  background: #f0f2f5;
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
  top: 30px;
  background: white;
  box-shadow: var(--shadow);
  border-radius: 8px;
  padding: 8px;
  z-index: 10;
}

.dropdown-menu button {
  display: block;
  width: 100%;
  padding: 8px 16px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  border-radius: 4px;
}

.dropdown-menu button:hover {
  background: var(--secondary-color);
}

.delete-btn {
  color: var(--error);
}
</style>
