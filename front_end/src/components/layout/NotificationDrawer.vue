<template>
  <Transition name="fade">
    <div v-if="show" class="drawer-overlay" @click.self="$emit('close')">
      <Transition name="slide">
        <div v-if="show" class="notif-drawer card">
          <div class="drawer-header">
            <h3>Notifications</h3>
            <button class="close-btn" @click="$emit('close')">
              <span class="material-symbols-rounded">close</span>
            </button>
          </div>
          <div class="drawer-body">
            <div v-if="notifications.length === 0" class="empty-notifs">
              <p>Aucune notification</p>
            </div>
            <div v-for="n in notifications" :key="n.id_notif" class="notif-item" :class="{ 'unread': !n.is_read }">
              <img :src="getAuthorAvatar(n.author)" class="notif-avatar" />
              <div class="notif-content">
                <p v-if="n.type === 'follow'"><strong>{{ n.author?.nom }}</strong> a commencé à vous suivre</p>
                <p v-else-if="n.type === 'follow_back'"><strong>{{ n.author?.nom }}</strong> vous a suivi en retour</p>
                <p v-else-if="n.type === 'mention'"><strong>{{ n.author?.nom }}</strong> vous a mentionné dans un post</p>

                <p v-else-if="n.type === 'report'"><strong>{{ n.author?.nom }}</strong> a soumis un signalement</p>
                <p v-else-if="n.type === 'new_user'"><strong>{{ n.author?.nom }}</strong> vient de s'inscrire</p>
                <p v-else-if="n.type === 'badge'">
                  Félicitations ! Vous avez reçu le badge <strong>{{ n.badge?.name }}</strong>
                </p>
                <p v-else><strong>{{ n.author?.nom }}</strong> a {{ n.type === 'like' ? 'liké' : 'commenté' }} votre post</p>
                
                <div v-if="n.type === 'badge'" class="badge-notif-preview" :style="{ color: n.badge?.color }">
                   <span class="material-symbols-rounded">{{ n.badge?.icon || 'stars' }}</span>
                </div>
                <span v-else-if="n.post" class="post-preview">"{{ n.post.description?.substring(0, 30) }}..."</span>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
import { watch } from 'vue';
import { BASE_URL } from '@/utils/api';

const props = defineProps({
  show: { type: Boolean, default: false },
  notifications: { type: Array, default: () => [] }
});

const emit = defineEmits(['close']);

// Handle scroll locking
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const getAuthorAvatar = (author) => {
  if (!author) return 'https://ui-avatars.com/api/?name=U';
  if (author.photo_profil) {
    return `${BASE_URL}/storage/${author.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + author.nom;
};
</script>

<style scoped>
.notif-drawer {
  position: fixed;
  right: 0;
  width: 360px;
  max-width: 90vw;
  height: calc(100vh - 60px);
  z-index: 300;
  overflow-y: auto;
  border-radius: 0;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-color);
}

.drawer-header h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-color);
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--secondary-color);
  color: var(--text-color);
}

.drawer-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.empty-notifs {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-muted);
}

.notif-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  transition: background 0.2s;
  cursor: pointer;
}

.notif-item:hover {
  background: var(--secondary-color);
}

.notif-item.unread {
  background: rgba(24, 119, 242, 0.05);
}

.notif-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.notif-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.notif-content p {
  font-size: 0.95rem;
  color: var(--text-color);
  line-height: 1.4;
}

.notif-content strong {
  font-weight: 600;
}

.post-preview {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-style: italic;
}

.badge-notif-preview {
  font-size: 2rem;
  margin-top: 5px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Overlay */
.drawer-overlay {
  position: fixed;
  top: 60px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 299;
  backdrop-filter: blur(2px);
}

.notif-drawer {
  position: fixed;
  top: 60px;
  right: 0;
  width: 360px;
  max-width: 90vw;
  height: calc(100vh - 60px);
  z-index: 300;
  overflow-y: auto;
  border-radius: 0;
}

/* Fade transition for overlay */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Slide transition for drawer */
.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}
</style>
