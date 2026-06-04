<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import AppButton from '@/components/atoms/AppButton.vue'
import LanguageSwitcher from '@/components/molecules/LanguageSwitcher.vue'
import { useAdminI18n } from '@/composables/useI18n'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const { user } = storeToRefs(auth)
const { t } = useAdminI18n()
const isCollapsed = ref(false)

const adminName = computed(() =>
  user.value === null ? '' : `${user.value.firstName} ${user.value.lastName}`,
)

const navGroups = computed(() => [
  {
    label: t.value.scheduling,
    items: [
      { label: t.value.nav.dashboard, route: { name: 'admin-dashboard' } },
      { label: t.value.nav.schedules, route: { name: 'admin-schedules' } },
      { label: t.value.nav.examSchedules, route: { name: 'admin-exam-schedules' } },
      { label: t.value.nav.generationJobs, route: { name: 'admin-generation-jobs' } },
    ],
  },
  {
    label: t.value.setupData,
    items: [
      { label: t.value.nav.groups, route: { name: 'admin-entity', params: { entity: 'groups' } } },
      { label: t.value.nav.teachers, route: { name: 'admin-entity', params: { entity: 'teachers' } } },
      { label: t.value.nav.subjects, route: { name: 'admin-entity', params: { entity: 'subjects' } } },
      {
        label: t.value.nav.teacherSubjects,
        route: { name: 'admin-entity', params: { entity: 'teacher-subjects' } },
      },
      {
        label: t.value.nav.teacherUnavailability,
        route: { name: 'admin-entity', params: { entity: 'teacher-unavailability' } },
      },
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
    ],
  },
  {
    label: t.value.operations,
    items: [{ label: t.value.nav.actionLog, route: { name: 'admin-action-log' } }],
  },
])

const activeTitle = computed(() => {
  for (const group of navGroups.value) {
    const item = group.items.find((candidate) => {
      if (candidate.route.name !== route.name) {
        return false
      }

      if ('params' in candidate.route && candidate.route.params !== undefined) {
        const params = candidate.route.params as { entity?: string }
        return params.entity === route.params.entity
      }

      return true
    })

    if (item !== undefined) {
      return item.label
    }
  }

  return t.value.activeSection
})

async function logout(): Promise<void> {
  auth.logout()
  await router.push({ name: 'admin-login' })
}
</script>

<template>
  <div :class="['admin-layout', { 'admin-layout--collapsed': isCollapsed }]">
    <aside class="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar__brand">
        <strong>{{ t.dashboard }}</strong>
        <button type="button" class="admin-sidebar__toggle" @click="isCollapsed = !isCollapsed">
          {{ isCollapsed ? '>' : '<' }}
        </button>
      </div>
      <nav class="admin-nav">
        <section v-for="group in navGroups" :key="group.label" class="admin-nav__group">
          <h2>{{ group.label }}</h2>
          <RouterLink v-for="item in group.items" :key="item.label" :to="item.route">
            {{ item.label }}
          </RouterLink>
        </section>
      </nav>
    </aside>
    <div class="admin-main">
      <header class="admin-topbar">
        <div>
          <small>{{ t.activeSection }}</small>
          <strong>{{ activeTitle }}</strong>
        </div>
        <div class="admin-topbar__actions">
          <span>
            {{ t.signedInAs }}
            <strong data-testid="admin-name">{{ adminName }}</strong>
          </span>
          <LanguageSwitcher :label="t.language" />
          <AppButton variant="ghost" data-testid="logout-button" @click="logout">
            {{ t.logout }}
          </AppButton>
        </div>
      </header>
      <section class="admin-content">
        <slot />
      </section>
    </div>
  </div>
</template>
