<template>
  <div class="home-view">
    <div v-if="loading" class="loader-wrapper">
      <Loader />
    </div>
    
    <div v-else class="feed-container">
      <div v-if="posts.length === 0" class="empty-state card">
        <div class="empty-icon">📭</div>
        <p>Aucun post pour le moment. Soyez le premier à poster !</p>
        <button class="btn btn-primary" @click="router.push(`/${authStore.user.nom}/add_post`)">
          Créer un post
        </button>
      </div>

      <div class="posts-feed">
        <div v-for="post in posts" :key="post.id_post" :id="'post-' + post.id_post">
          <PostCard 
            :post="post" 
            @refresh="fetchPosts"
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
      @comment-added="fetchPosts"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/api';
import PostCard from '@/components/PostCard.vue';
import CommentDrawer from '@/components/CommentDrawer.vue';
import Loader from '@/components/Loader.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const posts = ref([]);
const loading = ref(true);
const isDesktop = ref(window.innerWidth >= 768);

const isDrawerOpen = ref(false);
const activePostId = ref(null);

const openComments = (post) => {
  activePostId.value = post.id_post;
  isDrawerOpen.value = true;
};

const fetchPosts = async () => {
  try {
    const res = await api.get('/posts');
    posts.value = res.data;
    
    // Check for deep link
    if (route.params.post_id) {
        setTimeout(() => {
            const el = document.getElementById('post-' + route.params.post_id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.transition = 'transform 0.5s';
                el.style.transform = 'scale(1.02)';
                setTimeout(() => el.style.transform = 'scale(1)', 1000);
            }
        }, 500);
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const handleResize = () => {
    isDesktop.value = window.innerWidth >= 768;
};

onMounted(() => {
    fetchPosts();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
.home-view {
  padding: 10px 0 80px;
}

.loader-wrapper {
    height: 60vh;
    display: flex;
    align-items: center;
}

.welcome-banner {
    margin-bottom: 25px;
    padding: 0 10px;
}

.welcome-banner h2 {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 5px;
}

.welcome-banner p {
    color: var(--text-muted);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.posts-feed {
    max-width: 600px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}
</style>
