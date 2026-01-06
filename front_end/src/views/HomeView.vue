<template>
  <div class="home-view">
    <div v-if="loading" class="feed-container">
      <div class="filter-bar card">
        <div class="skeleton-line full-width"></div>
      </div>
      <div class="posts-feed">
        <PostSkeleton v-for="i in 3" :key="i" />
      </div>
    </div>
    
    <div v-else class="feed-container">
      <div class="filter-bar card">
        <div class="filter-scroll-container">
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

          <div class="filter-group">
            <span class="material-symbols-rounded">school</span>
            <input 
              type="text" 
              v-model="filters.etablissement" 
              placeholder="Établissement..." 
              @input="handleEtablissementSearch"
              class="filter-input"
            />
          </div>
        </div>

        <button v-if="filters.tag || filters.filiere || filters.etablissement" class="btn-reset" @click="resetFilters">
          <span class="material-symbols-rounded">restart_alt</span>
        </button>
      </div>

      <div v-if="posts.length === 0" class="empty-state card">
        <div class="empty-icon">📭</div>
        <p>Aucun post ne correspond à vos critères.</p>
        <br>
        <button class="btn btn-primary" @click="resetFilters">
          Voir tout
        </button>
      </div>

      <div class="home-grid">
        <div class="posts-feed">
          <div v-for="post in posts" :key="post.id_post" :id="'post-' + post.id_post">
            <PostCard 
              :post="post" 
              @refresh="fetchPosts(false)"
              @open-comments="openComments"
            />
          </div>
          
          <!-- Infinite Scroll Trigger -->
          <div ref="scrollTrigger" class="scroll-trigger">
            <Loader v-if="loadingMore" />
            <p v-else-if="!hasMore && posts.length > 0" class="no-more">Vous avez tout vu ! ✨</p>
          </div>
        </div>

        <!-- Desktop Sidebar -->
        <TrendingSidebar v-if="isDesktop" />
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
import PostSkeleton from '@/components/PostSkeleton.vue';
import CommentDrawer from '@/components/CommentDrawer.vue';
import Loader from '@/components/Loader.vue';
import TrendingSidebar from '@/components/layout/TrendingSidebar.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const posts = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const isDesktop = ref(window.innerWidth >= 768);

const isDrawerOpen = ref(false);
const activePostId = ref(null);

const scrollTrigger = ref(null);
const page = ref(1);
const hasMore = ref(true);

const filters = reactive({
  tag: '',
  filiere: '',
  etablissement: ''
});

let etablissementTimeout = null;
const handleEtablissementSearch = () => {
    if (etablissementTimeout) clearTimeout(etablissementTimeout);
    etablissementTimeout = setTimeout(() => {
        fetchPosts(false);
    }, 500);
};

const openComments = (post) => {
  activePostId.value = post.id_post;
  isDrawerOpen.value = true;
};

const resetFilters = () => {
  filters.tag = '';
  filters.filiere = '';
  filters.etablissement = '';
  fetchPosts(false);
};

const fetchPosts = async (append = false) => {
  if (append) {
    loadingMore.value = true;
  } else {
    loading.value = true;
    page.value = 1;
    hasMore.value = true;
  }

  try {
    const params = { page: page.value };
    if (filters.tag) params.tag = filters.tag;
    if (filters.filiere) params.filiere = filters.filiere;
    if (filters.etablissement) params.etablissement = filters.etablissement;

    const res = await api.get('/posts', { params });
    const responseData = res.data.data || res.data;
    const newPosts = Array.isArray(responseData) ? responseData : (responseData.data || []);
    
    if (append) {
      posts.value = [...posts.value, ...newPosts];
    } else {
      posts.value = newPosts;
    }

    // Check if there are more pages based on Laravel pagination structure
    if (res.data.meta) {
        hasMore.value = res.data.meta.current_page < res.data.meta.last_page;
    } else if (res.data.current_page) {
        hasMore.value = res.data.current_page < res.data.last_page;
    } else {
        hasMore.value = newPosts.length === 10;
    }

    // Check for deep link (only on first load)
    if (!append && route.params.post_id) {
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
    loadingMore.value = false;
  }
};

const handleInfiniteScroll = (entries) => {
    const target = entries[0];
    if (target.isIntersecting && hasMore.value && !loadingMore.value && !loading.value) {
        page.value++;
        fetchPosts(true);
    }
};

let observer = null;

const handleResize = () => {
    isDesktop.value = window.innerWidth >= 768;
};

onMounted(() => {
    // Initial filter from query if present
    if (route.query.tag) {
        filters.tag = route.query.tag;
    }
    fetchPosts();
    window.addEventListener('resize', handleResize);

    // Setup intersection observer
    observer = new IntersectionObserver(handleInfiniteScroll, {
        rootMargin: '100px',
        threshold: 0.1
    });
    if (scrollTrigger.value) {
        observer.observe(scrollTrigger.value);
    }
});

// Watch for query changes (when clicking a tag from another page or same page)
import { watch } from 'vue';
watch(() => route.query.tag, (newTag) => {
    filters.tag = newTag || '';
    fetchPosts(false);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    if (observer) observer.disconnect();
});
</script>

<style scoped>
.home-view {
  padding: 10px 0 80px;
}

.skeleton-line.full-width {
  width: 100%;
  height: 40px;
  background: var(--input-bg);
  border-radius: 8px;
  animation: pulse 1.5s infinite ease-in-out;
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
    margin: 0 10px 20px;
    padding: 10px 15px;
    display: flex;
    gap: 10px;
    align-items: center;
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
}

.filter-scroll-container {
    display: flex;
    gap: 15px;
    overflow-x: auto;
    flex: 1;
    padding: 5px 0;
    -webkit-overflow-scrolling: touch;
}

.filter-scroll-container::-webkit-scrollbar {
    display: none;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    white-space: nowrap;
    background: var(--input-bg);
    padding: 6px 12px;
    border-radius: 20px;
    flex-shrink: 0;
}

.filter-group select, .filter-input {
    border: none;
    background: none;
    font-weight: 600;
    color: var(--text-color);
    cursor: pointer;
    font-size: 0.85rem;
    outline: none;
}

.filter-input {
    width: 100px;
}

.btn-reset {
    background: var(--input-bg);
    border: none;
    color: var(--error);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .filter-bar {
        margin: 0 0 20px; /* Aligné à gauche sur desktop */
    }
    .filter-input {
        width: 150px;
    }
}

.home-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 1024px) {
    .home-grid {
        grid-template-columns: 1fr 300px;
        align-items: start;
    }
}

.posts-feed {
    width: 100%;
    max-width: 600px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.scroll-trigger {
    padding: 20px;
    display: flex;
    justify-content: center;
}

.no-more {
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 600;
}
</style>
