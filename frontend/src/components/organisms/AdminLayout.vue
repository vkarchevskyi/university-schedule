<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import AppButton from '@/components/atoms/AppButton.vue'
import LanguageSwitcher from '@/components/molecules/LanguageSwitcher.vue'
import { useAdminI18n } from '@/composables/useI18n'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const { user } = storeToRefs(auth)
const { t } = useAdminI18n()

const adminName = computed(() =>
  user.value === null ? '' : `${user.value.firstName} ${user.value.lastName}`,
)

const navItems = computed(() => [
  { label: t.value.nav.dashboard, route: { name: 'admin-dashboard' } },
  { label: t.value.nav.schedules, route: { name: 'admin-schedules' } },
  { label: t.value.nav.examSchedules, route: { name: 'admin-exam-schedules' } },
  { label: t.value.nav.groups, route: { name: 'admin-entity', params: { entity: 'groups' } } },
  { label: t.value.nav.teachers, route: { name: 'admin-entity', params: { entity: 'teachers' } } },
  { label: t.value.nav.subjects, route: { name: 'admin-entity', params: { entity: 'subjects' } } },
  { label: t.value.nav.rooms, route: { name: 'admin-entity', params: { entity: 'rooms' } } },
  { label: t.value.nav.timeSlots, route: { name: 'admin-entity', params: { entity: 'time-slots' } } },
  {
    label: t.value.nav.academicYears,
    route: { name: 'admin-entity', params: { entity: 'academic-years' } },
  },
  { label: t.value.nav.semesters, route: { name: 'admin-entity', params: { entity: 'semesters' } } },
  {
    label: t.value.nav.teachingLoads,
    route: { name: 'admin-entity', params: { entity: 'teaching-loads' } },
  },
])

async function logout(): Promise<void> {
  auth.logout()
  await router.push({ name: 'admin-login' })
}
</script>

<template>
  <div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <strong>{{ t.dashboard }}</strong>
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
          {{ t.signedInAs }}
          <strong data-testid="admin-name">{{ adminName }}</strong>
        </span>
        <LanguageSwitcher :label="t.language" />
        <AppButton variant="ghost" data-testid="logout-button" @click="logout">
          {{ t.logout }}
        </AppButton>
      </header>
      <section class="admin-content">
        <slot />
      </section>
    </div>
  </div>
</template>
