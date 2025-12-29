import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/login'
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guest: true }
    },
    {
      path: '/:nom_user/home',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { auth: true }
    },
    {
      path: '/:nom_user/profil/:target_name?',
      name: 'profile',
      component: () => import('@/views/ProfileView.vue'),
      meta: { auth: true }
    },
    {
      path: '/profil',
      redirect: to => {
        const authStore = useAuthStore();
        return authStore.user ? `/${authStore.user.nom}/profil` : '/login';
      }
    },
    {
      path: '/:nom_user/add_post',
      name: 'add-post',
      component: () => import('@/views/AddPostView.vue'),
      meta: { auth: true }
    },
    {
      path: '/:nom_user/edit_post/:postId',
      name: 'edit-post',
      component: () => import('@/views/AddPostView.vue'),
      meta: { auth: true }
    }
  ]
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;

  if (to.meta.auth && !isAuthenticated) {
    next('/login');
  } else if (to.meta.guest && isAuthenticated) {
    next(`/${authStore.user.nom}/home`);
  } else {
    next();
  }
});

export default router;
