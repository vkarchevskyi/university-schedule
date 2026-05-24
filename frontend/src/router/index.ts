import { createRouter, createWebHistory } from 'vue-router'

import AdminActionLogPage from '@/components/pages/AdminActionLogPage.vue'
import AdminDashboardPage from '@/components/pages/AdminDashboardPage.vue'
import AdminEntityPage from '@/components/pages/AdminEntityPage.vue'
import AdminExamScheduleEditorPage from '@/components/pages/AdminExamScheduleEditorPage.vue'
import AdminExamSchedulesPage from '@/components/pages/AdminExamSchedulesPage.vue'
import AdminGenerationJobsPage from '@/components/pages/AdminGenerationJobsPage.vue'
import AdminLoginPage from '@/components/pages/AdminLoginPage.vue'
import AdminScheduleEditorPage from '@/components/pages/AdminScheduleEditorPage.vue'
import AdminSchedulesPage from '@/components/pages/AdminSchedulesPage.vue'
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
    {
      path: '/admin/schedules',
      name: 'admin-schedules',
      component: AdminSchedulesPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/schedules/:id',
      name: 'admin-schedule-editor',
      component: AdminScheduleEditorPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/entities/:entity',
      name: 'admin-entity',
      component: AdminEntityPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/exam-schedules',
      name: 'admin-exam-schedules',
      component: AdminExamSchedulesPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/exam-schedules/:id',
      name: 'admin-exam-schedule-editor',
      component: AdminExamScheduleEditorPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/generation-jobs',
      name: 'admin-generation-jobs',
      component: AdminGenerationJobsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/action-log',
      name: 'admin-action-log',
      component: AdminActionLogPage,
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
      await auth.loadCurrentUser()
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
      await auth.loadCurrentUser()
      return true
    } catch {
      return { name: 'admin-login' }
    }
  }

  return { name: 'admin-login' }
})

export default router
