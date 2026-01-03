<template>
  <div v-if="isOpen" class="drawer-overlay" @click.self="$emit('close')">
    <div class="drawer-content card">
      <div class="drawer-header">
        <h3>Commentaires</h3>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="comments-body">
        <div v-if="loading" class="drawer-loader">
          <Loader />
        </div>
        
        <div v-else class="comments-list">
          <div v-if="comments.length === 0" class="empty-comments">
            <span class="icon">💬</span>
            <p>Aucun commentaire pour l'instant. Soyez le premier !</p>
          </div>
          
          <div v-for="comment in comments" :key="comment.id_commentaire" class="comment-item">
            <img :src="getAvatar(comment.user)" class="comment-avatar" />
            <div class="comment-text">
              <span class="comment-user">{{ comment.user.nom }}</span>
              <p class="comment-content">{{ comment.contenu }}</p>
              <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
            </div>
            <button v-if="isOwner(comment)" @click="deleteComment(comment)" class="delete-btn">🗑️</button>
          </div>
        </div>
      </div>

      <div class="drawer-footer">
        <form @submit.prevent="addComment" class="comment-form">
          <input 
            v-model="newComment" 
            type="text" 
            class="input-control" 
            placeholder="Ajouter un commentaire..."
            required
          >
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? '...' : 'Envoyer' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '@/utils/api';
import { useAuthStore } from '@/stores/auth';
import Loader from '@/components/Loader.vue';
import Swal from 'sweetalert2';

const props = defineProps(['isOpen', 'postId']);
const emit = defineEmits(['close', 'comment-added']);

const authStore = useAuthStore();
const comments = ref([]);
const loading = ref(false);
const submitting = ref(false);
const newComment = ref('');

const fetchComments = async () => {
  if (!props.postId) return;
  loading.value = true;
  try {
    const res = await api.get(`/posts/${props.postId}`);
    comments.value = res.data.comments || [];
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const addComment = async () => {
  submitting.value = true;
  try {
    const res = await api.post(`/posts/${props.postId}/comments`, {
      contenu: newComment.value
    });
    comments.value.unshift(res.data.comment);
    newComment.value = '';
    emit('comment-added');
  } catch (err) {
    console.error(err);
  } finally {
    submitting.value = false;
  }
};

const deleteComment = async (comment) => {  
  const result = await Swal.fire({
    title: 'Supprimer ce commentaire ?',
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
            await api.delete(`/comments/${comment.id_commentaire}`);
                  Swal.fire({
                  title: 'Supprimé !',
                  text: 'Votre commentaire a été supprimé.',
                  icon: 'success',
                  timer: 1500,
                  showConfirmButton: false
           });
            comments.value = comments.value.filter(c => c.id_commentaire !== comment.id_commentaire);
            emit('comment-added');
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

const isOwner = (comment) => authStore.user?.id === comment.id_user;

const getAvatar = (user) => {
  if (!user.photo_profil) return 'https://via.placeholder.com/32';
  return user.photo_profil.startsWith('http') ? user.photo_profil : `http://localhost:8000/storage/${user.photo_profil}`;
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

watch(() => props.postId, fetchComments);
onMounted(fetchComments);
</script>

<style scoped>
.drawer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 200;
  display: flex;
  justify-content: flex-end;
}

.drawer-content {
  width: 100%;
  max-width: 450px;
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: 0;
  padding: 0;
}

@media (max-width: 767px) {
    .drawer-overlay {
        align-items: flex-end;
    }
    .drawer-content {
        height: 80%;
        border-radius: 20px 20px 0 0;
    }
}

.drawer-header {
  padding: 20px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-color);
  cursor: pointer;
}

.comments-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  position: relative;
}

.drawer-loader {
    height: 100%;
    display: flex;
    align-items: center;
}

.comment-item {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  position: relative;
}

.comment-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.comment-text {
  background: var(--input-bg);
  padding: 10px 15px;
  border-radius: 18px;
  flex: 1;
}

.comment-user {
  font-weight: 700;
  font-size: 0.85rem;
  display: block;
}

.comment-content {
  margin: 4px 0;
  font-size: 0.95rem;
}

.comment-date {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.empty-comments {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-muted);
}

.empty-comments .icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 15px;
}

.drawer-footer {
  padding: 20px;
  border-top: 1px solid var(--border-color);
  background: var(--card-bg);
}

.comment-form {
  display: flex;
  gap: 10px;
}

.delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    opacity: 0.6;
}

.delete-btn:hover {
    opacity: 1;
}
</style>
