<template>
  <div class="post-skeleton card">
    <div class="skeleton-header">
      <div class="skeleton-avatar"></div>
      <div class="skeleton-user-info">
        <div class="skeleton-line short"></div>
        <div class="skeleton-line x-short"></div>
      </div>
    </div>
    
    <div class="skeleton-body">
      <div class="skeleton-tags">
        <div class="skeleton-tag" v-for="i in 3" :key="i"></div>
      </div>
      <div class="skeleton-image"></div>
      <div class="skeleton-line"></div>
      <div class="skeleton-line half"></div>
    </div>
    
    <div class="skeleton-footer">
      <div class="skeleton-action" v-for="i in 3" :key="i"></div>
    </div>
  </div>
</template>

<style scoped>
.post-skeleton {
  margin-bottom: 20px;
  overflow: hidden;
}

.skeleton-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.skeleton-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--input-bg);
  animation: pulse 1.5s infinite ease-in-out;
}

.skeleton-user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-line {
  height: 12px;
  background: var(--input-bg);
  border-radius: 4px;
  animation: pulse 1.5s infinite ease-in-out;
}

.skeleton-line.short { width: 40%; }
.skeleton-line.x-short { width: 20%; }
.skeleton-line.half { width: 60%; }

.skeleton-body {
  margin-bottom: 20px;
}

.skeleton-tags {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.skeleton-tag {
  width: 60px;
  height: 24px;
  border-radius: 20px;
  background: var(--input-bg);
  animation: pulse 1.5s infinite ease-in-out;
}

.skeleton-image {
  width: 100%;
  aspect-ratio: 1 / 1;
  background: var(--input-bg);
  border-radius: 8px;
  margin-bottom: 12px;
  animation: pulse 1.5s infinite ease-in-out;
}

.skeleton-footer {
  display: flex;
  justify-content: space-around;
  padding-top: 15px;
  border-top: 1px solid var(--border-color);
}

.skeleton-action {
  width: 60px;
  height: 30px;
  border-radius: 6px;
  background: var(--input-bg);
  animation: pulse 1.5s infinite ease-in-out;
}

@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 0.3; }
  100% { opacity: 0.6; }
}
</style>
