<template>
  <div class="home-view">
    <div v-if="loading" class="feed-container">
      <div class="posts-feed">
        <PostSkeleton v-for="i in 3" :key="i" />
      </div>
    </div>
    
    <div v-else class="feed-container">
      <!-- Hashtag Filter Header -->
      <div v-if="filters.tag" class="hashtag-filter-header card">
        <h2 class="hashtag-title">
          <span class="material-symbols-rounded">tag</span>
          Tous les posts avec #{{ filters.tag }}
        </h2>
        <button class="btn-close-filter" @click="resetFilters" title="Fermer">
          <span class="material-symbols-rounded">close</span>
        </button>
      </div>

      <div v-if="posts.length === 0" class="empty-state card">
        <div class="empty-icon">📭</div>
        <p v-if="filters.tag">Aucun post avec le hashtag #{{ filters.tag }}</p>
        <p v-else>Aucun post disponible pour le moment.</p>
        <br>
        <button v-if="filters.tag" class="btn btn-primary" @click="resetFilters">
          Voir tous les posts
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
            <AppLoader v-if="loadingMore" />
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
import AppLoader from '@/components/Loader.vue';
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
  tag: ''
});



const openComments = (post) => {
  activePostId.value = post.id_post;
  isDrawerOpen.value = true;
};

const resetFilters = () => {
  filters.tag = '';
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
    
    // Setup observer after first load (when DOM is ready)
    if (!append) {
      setupObserver();
    }
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

const setupObserver = () => {
    if (observer) return; // Already set up
    
    import('vue').then(({ nextTick }) => {
        nextTick(() => {
            if (scrollTrigger.value) {
                observer = new IntersectionObserver(handleInfiniteScroll, {
                    rootMargin: '100px',
                    threshold: 0.1
                });
                observer.observe(scrollTrigger.value);
            }
        });
    });
};

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

/* Hashtag Filter Header */
.hashtag-filter-header {
    max-width: 600px;
    margin: 0 10px 20px;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    background: var(--card-bg);
    border-radius: 12px;
}

.hashtag-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-color);
    margin: 0;
}

.hashtag-title .material-symbols-rounded {
    color: var(--primary-color);
}

.btn-close-filter {
    background: var(--input-bg);
    border: none;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-close-filter:hover {
    background: var(--error);
    color: white;
}

@media (min-width: 768px) {
    .hashtag-filter-header {
        margin: 0 0 20px;
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
