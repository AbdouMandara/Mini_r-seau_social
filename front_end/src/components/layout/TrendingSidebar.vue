<template>
  <div class="trending-sidebar card desktop-only">
    <div class="sidebar-header">
      <span class="material-symbols-rounded">trending_up</span>
      <h3>Tendances</h3>
    </div>
    
    <div v-if="loading" class="sidebar-loader">
      <div class="skeleton-line" v-for="i in 5" :key="i"></div>
    </div>
    
    <div v-else class="trending-list">
      <div 
        v-for="tag in tags" 
        :key="tag.tag" 
        class="trending-item"
        @click="filterByTag(tag.tag)"
      >
        <span class="tag-name">#{{ tag.tag }}</span>
        <span class="tag-count">{{ tag.total }} publications</span>
      </div>
      
      <div v-if="tags.length === 0" class="empty-trending">
        Aucune tendance pour le moment.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';

const router = useRouter();
const tags = ref([]);
const loading = ref(true);

const fetchTags = async () => {
  try {
    const res = await api.get('/tags');
    tags.value = res.data.slice(0, 10); // Top 10
  } catch (err) {
    console.error('Error fetching trending tags:', err);
  } finally {
    loading.value = false;
  }
};

const filterByTag = (tag) => {
    router.push({ name: 'home', query: { tag } });
};

onMounted(fetchTags);
</script>

<style scoped>
.trending-sidebar {
  position: sticky;
  top: 80px;
  height: fit-content;
  padding: 20px;
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  color: var(--primary-color);
}

.sidebar-header h3 {
  font-size: 1.2rem;
  margin: 0;
  font-weight: 800;
  color: var(--text-color);
}

.trending-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.trending-item {
  display: flex;
  flex-direction: column;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: background 0.2s;
}

.trending-item:hover {
  background: var(--secondary-color);
}

.tag-name {
  font-weight: 700;
  color: var(--text-color);
}

.tag-count {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.skeleton-line {
  height: 40px;
  background: var(--input-bg);
  border-radius: 8px;
  margin-bottom: 15px;
  animation: pulse 1.5s infinite ease-in-out;
}

@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 0.3; }
  100% { opacity: 0.6; }
}

.empty-trending {
  text-align: center;
  color: var(--text-muted);
  font-size: 0.9rem;
  padding: 20px 0;
}

@media (max-width: 1024px) {
  .desktop-only {
    display: none;
  }
}
</style>
