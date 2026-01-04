<template>
  <div class="home-view">
    <div v-if="loading" class="loader-wrapper">
      <Loader />
    </div>
    
    <div v-else class="feed-container">
      <div class="filter-bar card">
        <div class="filter-group">
          <span class="material-symbols-rounded">filter_list</span>
          <select v-model="filters.tag" @change="fetchPosts">
            <option value="">Tous les tags</option>
            <option value="etude">Étude</option>
            <option value="divertissement">Divertissement</option>
            <option value="info">Information</option>
            <option value="programmation">Programmation</option>
            <option value="maths">Mathématiques</option>
            <option value="devoir">Devoir</option>
          </select>
        </div>

        <div class="filter-group">
          <span class="material-symbols-rounded">category</span>
          <select v-model="filters.filiere" @change="fetchPosts">
            <option value="">Toutes les filières</option>
            <option value="GL">GL</option>
            <option value="GLT">GLT</option>
            <option value="SWE">SWE</option>
            <option value="MVC">MVC</option>
            <option value="LTM">LTM</option>
          </select>
        </div>

        <button v-if="filters.tag || filters.filiere" class="btn-reset" @click="resetFilters">
          Réinitialiser
        </button>
      </div>

      <div v-if="posts.length === 0" class="empty-state card">
        <div class="empty-icon">📭</div>
        <p>Aucun post ne correspond à vos critères.</p>
        <button class="btn btn-primary" @click="resetFilters">
          Voir tout
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
import { ref, reactive, onMounted, onUnmounted } from 'vue';
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

const filters = reactive({
  tag: '',
  filiere: ''
});

const openComments = (post) => {
  activePostId.value = post.id_post;
  isDrawerOpen.value = true;
};

const resetFilters = () => {
  filters.tag = '';
  filters.filiere = '';
  fetchPosts();
};

const fetchPosts = async () => {
  try {
    const params = {};
    if (filters.tag) params.tag = filters.tag;
    if (filters.filiere) params.filiere = filters.filiere;

    const res = await api.get('/posts', { params });
    posts.value = res.data.data || res.data;
    
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

.filter-bar {
    max-width: 600px;
    margin: 0 auto 20px;
    padding: 15px 20px;
    display: flex;
    gap: 20px;
    align-items: center;
    background: var(--card-bg);
    border-radius: 15px;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
}

.filter-group select {
    border: none;
    background: none;
    font-weight: 600;
    color: var(--text-color);
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-reset {
    background: none;
    border: none;
    color: var(--primary-color);
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    margin-left: auto;
}

.posts-feed {
    max-width: 600px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}
</style>
