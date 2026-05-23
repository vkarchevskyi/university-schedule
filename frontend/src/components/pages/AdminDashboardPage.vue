<script setup lang="ts">
import { RouterLink } from 'vue-router'

import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminI18n } from '@/composables/useI18n'

const { t } = useAdminI18n()
</script>

<template>
  <AdminLayout>
    <div class="admin-dashboard" data-testid="admin-dashboard">
      <h1>{{ t.dashboard }}</h1>
      <p>{{ t.dashboardIntro }}</p>
      <div class="admin-dashboard__shortcuts">
        <RouterLink :to="{ name: 'admin-schedules' }">{{ t.nav.schedules }}</RouterLink>
        <RouterLink :to="{ name: 'admin-exam-schedules' }">{{ t.nav.examSchedules }}</RouterLink>
        <RouterLink :to="{ name: 'admin-entity', params: { entity: 'teaching-loads' } }">
          {{ t.nav.teachingLoads }}
        </RouterLink>
        <RouterLink :to="{ name: 'admin-entity', params: { entity: 'groups' } }">
          {{ t.nav.groups }}
        </RouterLink>
      </div>
    </div>
  </AdminLayout>
</template>
