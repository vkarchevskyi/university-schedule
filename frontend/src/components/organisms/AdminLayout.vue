<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import AppButton from '@/components/atoms/AppButton.vue'
import { adminCopy } from '@/i18n/admin'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const { admin } = storeToRefs(auth)

const adminName = computed(() => (admin.value === null ? '' : `${admin.value.firstName} ${admin.value.lastName}`))

const navItems = [
  adminCopy.nav.schedules,
  adminCopy.nav.groups,
  adminCopy.nav.teachers,
  adminCopy.nav.subjects,
  adminCopy.nav.rooms,
  adminCopy.nav.timeSlots,
  adminCopy.nav.generationJobs,
  adminCopy.nav.examSchedules,
]

async function logout(): Promise<void> {
  auth.logout()
  await router.push({ name: 'admin-login' })
}
</script>

<template>
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <strong>{{ adminCopy.dashboard }}</strong>
      <nav>
        <a v-for="item in navItems" :key="item" href="#" @click.prevent>{{ item }}</a>
      </nav>
    </aside>
    <div class="admin-main">
      <header class="admin-topbar">
        <span>
          {{ adminCopy.signedInAs }}
          <strong data-testid="admin-name">{{ adminName }}</strong>
        </span>
        <AppButton variant="ghost" data-testid="logout-button" @click="logout">
          {{ adminCopy.logout }}
        </AppButton>
      </header>
      <section class="admin-content">
        <slot />
      </section>
    </div>
  </div>
</template>
