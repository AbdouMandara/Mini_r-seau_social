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
    {
      path: '/admin',
      redirect: '/admin/dashboard',
      meta: { auth: true, admin: true }
    },
    {
       path: '/admin/dashboard',
       name: 'admin-dashboard',
       component: () => import('@/views/admin/AdminDashboardView.vue'),
       meta: { auth: true, admin: true }
    },
    {
        path: '/admin/feedbacks',
        name: 'admin-feedbacks',
        component: () => import('@/views/admin/AdminFeedbacksView.vue'),
        meta: { auth: true, admin: true }
    },
    {
        path: '/admin/activites',
        name: 'admin-activities',
        component: () => import('@/views/admin/AdminActivityView.vue'),
        meta: { auth: true, admin: true }
    },
    {
        path: '/admin/badges',
        name: 'admin-badges',
        component: () => import('@/views/admin/AdminBadgesView.vue'),
        meta: { auth: true, admin: true }
    },
    {
        path: '/admin/signalements',
        name: 'admin-reports',
        component: () => import('@/views/admin/AdminReportsView.vue'),
        meta: { auth: true, admin: true }
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
  

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  // Restore session if needed
  const hasLoggedInFlag = localStorage.getItem('pozterr_logged_in') === 'true';
  if (hasLoggedInFlag && !authStore.user) {
    try {
        await authStore.fetchProfile();
    } catch (error) {
        // Token likely invalid or session expired
        authStore.user = null;
        localStorage.removeItem('pozterr_logged_in');
        next('/login');
        return;
    }
  }

  const isAuthenticated = authStore.isAuthenticated;
  const user = authStore.user;

  if (to.meta.auth && !isAuthenticated) {
    next('/login');
    return;
  }

  if (isAuthenticated && user) {
    // Admin Isolation
    if (user.is_admin) {
        // If trying to access non-admin pages (except login/blocked which are handled elsewhere or irrelevant)
        // We allow accessing /admin/* and maybe some specifics, but user said "que à la page admin"
        if (!to.path.startsWith('/admin') && to.name !== 'login') {
             next('/admin/dashboard');
             return;
        }
    }

    // Guest pages for logged in users
    if (to.meta.guest) {
      if (user.is_admin) {
        next('/admin/dashboard');
      } else {
        const username = (user.slug || user.nom || 'me').replace(/ /g, '_');
        next(`/${username}/home`);
      }
      return;
    }

    // Blocked User
    if (user.is_blocked && to.name !== 'blocked') {
        next('/user_bloque');
        return;
    }
    if (to.name === 'blocked' && !user.is_blocked) {
        next(`/${user.nom}/home`);
        return;
    }

    // Protected Admin Routes for non-admins
    if (to.meta.admin && !user.is_admin) {
        next(`/${user.nom}/home`);
        return;
    }
  }

  next();
});

export default router;
