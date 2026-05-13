<script setup lang="ts">
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { adminCopy } from '@/i18n/admin'
</script>

<template>
  <AdminLayout>
    <div class="admin-dashboard" data-testid="admin-dashboard">
      <h1>{{ adminCopy.dashboard }}</h1>
      <p>{{ adminCopy.dashboardIntro }}</p>
    </div>
  </AdminLayout>
</template>
