<template>
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
            <p v-if="n.type === 'follow'"><strong>{{ n.author.nom }}</strong> a commencé à vous suivre</p>
            <p v-else-if="n.type === 'follow_back'"><strong>{{ n.author.nom }}</strong> vous a suivi en retour</p>
            <p v-else><strong>{{ n.author.nom }}</strong> a {{ n.type === 'like' ? 'liké' : 'commenté' }} votre post</p>
            <span v-if="n.post" class="post-preview">"{{ n.post.description.substring(0, 30) }}..."</span>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { BASE_URL } from '@/utils/api';

defineProps({
  show: { type: Boolean, default: false },
  notifications: { type: Array, default: () => [] }
});

defineEmits(['close']);

const getAuthorAvatar = (author) => {
  if (author.photo_profil) {
    return `${BASE_URL}/storage/${author.photo_profil}`;
  }
  return 'https://ui-avatars.com/api/?name=' + author.nom;
};
</script>

<style scoped>
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

.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}
</style>
