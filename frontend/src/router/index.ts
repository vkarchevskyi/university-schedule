import { createRouter, createWebHistory } from 'vue-router'

import AdminDashboardPage from '@/components/pages/AdminDashboardPage.vue'
import AdminLoginPage from '@/components/pages/AdminLoginPage.vue'
import PublicSchedulePage from '@/components/pages/PublicSchedulePage.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'public-schedule',
      component: PublicSchedulePage,
    },
    {
      path: '/admin/login',
      name: 'admin-login',
      component: AdminLoginPage,
    },
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: AdminDashboardPage,
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.name === 'admin-login' && auth.isAuthenticated) {
    return { name: 'admin-dashboard' }
  }

  if (to.name === 'admin-login' && auth.token !== null) {
    try {
      await auth.loadCurrentAdmin()
      return { name: 'admin-dashboard' }
    } catch {
      return true
    }
  }

  if (!to.meta.requiresAuth) {
    return true
  }

  if (auth.isAuthenticated) {
    return true
  }

  if (auth.token !== null) {
    try {
      await auth.loadCurrentAdmin()
      return true
    } catch {
      return { name: 'admin-login' }
    }
  }

  return { name: 'admin-login' }
})

export default router
