<script setup lang="ts">
import { RouterLink } from 'vue-router'

import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { adminCopy } from '@/i18n/admin'
</script>

<template>
  <AdminLayout>
    <div class="admin-dashboard" data-testid="admin-dashboard">
      <h1>{{ adminCopy.dashboard }}</h1>
      <p>{{ adminCopy.dashboardIntro }}</p>
      <div class="admin-dashboard__shortcuts">
        <RouterLink :to="{ name: 'admin-schedules' }">{{ adminCopy.nav.schedules }}</RouterLink>
        <RouterLink :to="{ name: 'admin-exam-schedules' }">{{ adminCopy.nav.examSchedules }}</RouterLink>
        <RouterLink :to="{ name: 'admin-entity', params: { entity: 'teaching-loads' } }">
          {{ adminCopy.nav.teachingLoads }}
        </RouterLink>
        <RouterLink :to="{ name: 'admin-entity', params: { entity: 'groups' } }">
          {{ adminCopy.nav.groups }}
        </RouterLink>
      </div>
    </div>
  </AdminLayout>
</template>
