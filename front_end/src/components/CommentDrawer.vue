<template>
  <div v-if="isOpen" class="drawer-overlay" @click.self="$emit('close')">
    <div class="drawer-content card">
      <div class="drawer-header">
        <h3>Commentaires</h3>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="comments-body">
        <div v-if="loading" class="drawer-loader">
          <AppLoader />
        </div>
        
        <div v-else class="comments-list" @click="handleMentionClick">
          <div v-if="comments.length === 0" class="empty-comments">
            <span class="icon">💬</span>
            <p>Aucun commentaire pour l'instant. Soyez le premier !</p>
          </div>
          
          <div v-for="comment in comments" :key="comment.id_commentaire" class="comment-item">
            <img :src="getAvatar(comment.user)" class="comment-avatar" />
            <div class="comment-text">
              <span class="comment-user">{{ comment.user?.nom || 'Utilisateur' }}</span>
              <p class="comment-content" v-html="formatMentions(comment.contenu)"></p>
              <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
            </div>
            <button v-if="isOwner(comment)" @click="deleteComment(comment)" class="delete-btn">🗑️</button>
          </div>
        </div>
      </div>

      <div class="drawer-footer">
        <!-- Mentions Suggestions -->
        <div v-if="suggestions.length > 0" class="mentions-suggestions">
          <div 
            v-for="user in suggestions" 
            :key="user.id" 
            class="suggestion-item"
            @click="insertMention(user)"
          >
            <img :src="getAvatar(user)" class="suggestion-avatar" />
            <span class="suggestion-name">{{ user.nom }}</span>
          </div>
        </div>

        <form @submit.prevent="addComment" class="comment-form">
          <input 
            v-model="newComment" 
            type="text" 
            class="input-control" 
            placeholder="Ajouter un commentaire (utilisez @ pour mentionner)"
            @input="handleInput"
            @keydown.esc="suggestions = []"
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
import api, { BASE_URL } from '@/utils/api';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import AppLoader from '@/components/Loader.vue';
import Swal from 'sweetalert2';

const props = defineProps(['isOpen', 'postId']);
const emit = defineEmits(['close', 'comment-added']);

const authStore = useAuthStore();
const comments = ref([]);
const loading = ref(false);
const submitting = ref(false);
const newComment = ref('');
const suggestions = ref([]);
const router = useRouter();

const handleInput = async (e) => {
    const text = newComment.value;
    const cursor = e.target.selectionStart;
    const textAroundCursor = text.slice(0, cursor);
    const lastAtIdx = textAroundCursor.lastIndexOf('@');

    if (lastAtIdx !== -1) {
        const query = textAroundCursor.slice(lastAtIdx + 1);
        if (query.length >= 2 && !query.includes(' ')) {
            try {
                const res = await api.get(`/users/search?query=${query}`);
                suggestions.value = res.data.data || res.data;
            } catch (err) {
                console.error(err);
            }
        } else {
            suggestions.value = [];
        }
    } else {
        suggestions.value = [];
    }
};

const insertMention = (user) => {
    const text = newComment.value;
    const lastAtIdx = text.lastIndexOf('@');
    const beforeAt = text.slice(0, lastAtIdx);
    const afterAt = text.slice(lastAtIdx);
    // Find where the mention query ends (first space or end of string)
    const nextSpace = afterAt.indexOf(' ');
    const remainingText = nextSpace === -1 ? '' : afterAt.slice(nextSpace);
    
    newComment.value = `${beforeAt}@${user.nom} ${remainingText}`;
    suggestions.value = [];
};

const formatMentions = (text) => {
    if (!text) return '';
    // XSS Protection: Escape HTML tags first
    const escaped = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    
    // Replace @Mention with clickable span
    // We match @ followed by word characters or spaces, but we stop at punctuation or non-name chars.
    // The user wants "@Name" in blue, and what follows in white.
    return escaped.replace(/@([a-zA-Z0-9_\u00C0-\u017F]+(?:\s[a-zA-Z0-9_\u00C0-\u017F]+)*)/g, (match, name) => {
        return `<span class="mention" data-name="${name}">@${name}</span>`;
    });
};

const handleMentionClick = (e) => {
    if (e.target.classList.contains('mention')) {
        const name = e.target.getAttribute('data-name');
        router.push(`/${authStore.user.nom}/profil/${name.replace(/ /g, '_')}`);
        emit('close');
    }
};

const fetchComments = async () => {
  if (!props.postId) return;
  loading.value = true;
  try {
    const res = await api.get(`/posts/${props.postId}`);
    const postData = res.data.data || res.data;
    comments.value = postData.comments || [];
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
    const commentData = res.data.comment?.data || res.data.comment;
    comments.value.unshift(commentData);
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
  if (!user?.photo_profil) return 'https://ui-avatars.com/api/?name=' + (user?.nom || 'User');
  return user.photo_profil.startsWith('http') ? user.photo_profil : `${BASE_URL}/storage/${user.photo_profil}`;
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

watch(() => props.isOpen, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

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
  max-width: 500px;
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: 0;
  padding: 0;
  box-shadow: -10px 0 30px rgba(0,0,0,0.1);
}

@media (max-width: 991px) {
    .drawer-content {
        max-width: 400px;
    }
}

@media (max-width: 767px) {
    .drawer-overlay {
        align-items: flex-end;
    }
    .drawer-content {
        max-width: 100%;
        height: 85%;
        border-radius: 25px 25px 0 0;
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

/* Mentions Styles */
:deep(.mention) {
    color: var(--primary-color);
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}

:deep(.mention:hover) {
    text-decoration: underline;
}

.mentions-suggestions {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 10px;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
}

.suggestion-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    cursor: pointer;
    transition: background 0.2s;
}

.suggestion-item:hover {
    background: var(--input-bg);
}

.suggestion-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.suggestion-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-color);
}
</style>
