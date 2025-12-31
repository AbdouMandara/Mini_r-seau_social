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
      path: '/:nom_user/home/:post_id?',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { auth: true }
    },
    {
      path: '/:nom_user/feedback',
      name: 'feedback',
      component: () => import('@/views/FeedbackView.vue'),
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
      path: '/:nom_user/edit_post/:postId',
      name: 'edit-post',
      component: () => import('@/views/AddPostView.vue'),
      meta: { auth: true }
    },
    // Admin Routes
    {
      path: '/admin',
      component: () => import('@/views/admin/AdminLayout.vue'),
      meta: { auth: true, admin: true },
      children: [
        {
          path: 'dashboard',
          component: () => import('@/views/admin/AdminDashboardView.vue'),
        },
        {
          path: 'users',
          component: () => import('@/views/admin/AdminUsersView.vue'),
        },
        {
            path: 'feedbacks',
            component: () => import('@/views/admin/AdminFeedbacksView.vue'),
        }
      ]
    },
    // Blocked Route
    {
      path: '/user_bloque',
      name: 'blocked',
      component: () => import('@/views/BlockedUserView.vue'),
      meta: { auth: true }
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
      // ... (content replaced by previous call but I need to make sure I don't break JSON structure) 
      // Actually I missed re-adding add-post in previous call so I will add it back here
      // And update the guards
      component: () => import('@/views/AddPostView.vue'),
      meta: { auth: true }
      }
    ]
    // ... admin routes are already there
  });
  

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;
  const user = authStore.user;

  if (to.meta.auth && !isAuthenticated) {
    next('/login');
    return;
  }

  if (isAuthenticated) {
    if (to.meta.guest) {
      if (user.is_admin) {
        next('/admin/dashboard');
      } else {
        next(`/${user.nom}/home`);
      }
      return;
    }

    if (user.is_blocked && to.name !== 'blocked') {
        next('/user_bloque');
        return;
    }

    if (to.name === 'blocked' && !user.is_blocked) {
        next(`/${user.nom}/home`);
        return;
    }

    if (to.meta.admin && !user.is_admin) {
        next(`/${user.nom}/home`);
        return;
    }
  }

  next();
});

export default router;
