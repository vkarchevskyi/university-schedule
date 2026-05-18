<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import AppButton from '@/components/atoms/AppButton.vue'
import { adminCopy } from '@/i18n/admin'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const { user } = storeToRefs(auth)

const adminName = computed(() =>
  user.value === null ? '' : `${user.value.firstName} ${user.value.lastName}`,
)

const navItems = [
  { label: adminCopy.nav.schedules, route: { name: 'admin-schedules' } },
  { label: adminCopy.nav.groups },
  { label: adminCopy.nav.teachers },
  { label: adminCopy.nav.subjects },
  { label: adminCopy.nav.rooms },
  { label: adminCopy.nav.timeSlots },
  { label: adminCopy.nav.generationJobs },
  { label: adminCopy.nav.examSchedules },
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
        <RouterLink
          v-for="item in navItems"
          :key="item.label"
          :to="item.route ?? { name: 'admin-dashboard' }"
        >
          {{ item.label }}
        </RouterLink>
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
