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
  { label: adminCopy.nav.dashboard, route: { name: 'admin-dashboard' } },
  { label: adminCopy.nav.schedules, route: { name: 'admin-schedules' } },
  { label: adminCopy.nav.examSchedules, route: { name: 'admin-exam-schedules' } },
  { label: adminCopy.nav.groups, route: { name: 'admin-entity', params: { entity: 'groups' } } },
  { label: adminCopy.nav.teachers, route: { name: 'admin-entity', params: { entity: 'teachers' } } },
  { label: adminCopy.nav.subjects, route: { name: 'admin-entity', params: { entity: 'subjects' } } },
  { label: adminCopy.nav.rooms, route: { name: 'admin-entity', params: { entity: 'rooms' } } },
  { label: adminCopy.nav.timeSlots, route: { name: 'admin-entity', params: { entity: 'time-slots' } } },
  {
    label: adminCopy.nav.academicYears,
    route: { name: 'admin-entity', params: { entity: 'academic-years' } },
  },
  { label: adminCopy.nav.semesters, route: { name: 'admin-entity', params: { entity: 'semesters' } } },
  {
    label: adminCopy.nav.teachingLoads,
    route: { name: 'admin-entity', params: { entity: 'teaching-loads' } },
  },
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
          :to="item.route"
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
