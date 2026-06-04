<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { listGenerationJobs, listSchedules, listSemesters } from '@/api/adminSchedule'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminI18n } from '@/composables/useI18n'
import type { AdminSchedule, AdminSemester, ScheduleGenerationJob } from '@/types/adminSchedule'

const { t } = useAdminI18n()
const schedules = ref<AdminSchedule[]>([])
const semesters = ref<AdminSemester[]>([])
const generationJobs = ref<ScheduleGenerationJob[]>([])
const isLoading = ref(true)

const activeSemester = computed(() => semesters.value[0] ?? null)
const latestDraft = computed(() =>
  [...schedules.value]
    .filter((schedule) => schedule.status !== 'published')
    .sort((left, right) => right.id - left.id)[0] ?? null,
)
const unpublishedCount = computed(
  () => schedules.value.filter((schedule) => schedule.status !== 'published').length,
)
const recentGeneration = computed(() => generationJobs.value[0] ?? null)

onMounted(loadOverview)

async function loadOverview(): Promise<void> {
  isLoading.value = true

  try {
    const [semesterResponse, scheduleResponse, jobResponse] = await Promise.all([
      listSemesters(),
      listSchedules(),
      listGenerationJobs(),
    ])
    semesters.value = semesterResponse.items
    schedules.value = scheduleResponse.items
    generationJobs.value = jobResponse.items
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard-page" data-testid="admin-dashboard">
      <header class="admin-page-header">
        <div>
          <h1>{{ t.dashboard }}</h1>
          <p>{{ t.dashboardIntro }}</p>
        </div>
      </header>

      <div class="overview-grid" aria-live="polite">
        <article class="overview-card">
          <span>{{ t.activeSemester }}</span>
          <strong v-if="activeSemester">{{ t.semester }} {{ activeSemester.number }}</strong>
          <strong v-else>{{ t.notAvailable }}</strong>
          <small v-if="activeSemester">{{ activeSemester.startsAt }} - {{ activeSemester.endsAt }}</small>
        </article>
        <article class="overview-card">
          <span>{{ t.latestDraft }}</span>
          <strong v-if="latestDraft">#{{ latestDraft.id }}</strong>
          <strong v-else>{{ t.notAvailable }}</strong>
          <small v-if="latestDraft">{{ t.scheduleStatuses[latestDraft.status] ?? latestDraft.status }}</small>
        </article>
        <article class="overview-card">
          <span>{{ t.unpublishedSchedules }}</span>
          <strong>{{ isLoading ? '...' : unpublishedCount }}</strong>
          <small>{{ t.scheduleQueue }}</small>
        </article>
        <article class="overview-card">
          <span>{{ t.recentGeneration }}</span>
          <strong v-if="recentGeneration">{{ recentGeneration.status }}</strong>
          <strong v-else>{{ t.notAvailable }}</strong>
          <small v-if="recentGeneration && recentGeneration.qualityScore !== null">
            {{ t.qualityScore }}: {{ recentGeneration.qualityScore }}
          </small>
        </article>
      </div>

      <section class="quick-action-panel">
        <h2>{{ t.quickActions }}</h2>
        <div class="admin-dashboard__shortcuts">
          <RouterLink :to="{ name: 'admin-schedules' }">{{ t.createSchedule }}</RouterLink>
          <RouterLink :to="{ name: 'admin-schedules' }">{{ t.generateSchedule }}</RouterLink>
          <RouterLink :to="{ name: 'admin-entity', params: { entity: 'teaching-loads' } }">
            {{ t.editTeachingLoads }}
          </RouterLink>
          <RouterLink :to="{ name: 'admin-generation-jobs' }">
            {{ t.nav.generationJobs }}
          </RouterLink>
        </div>
      </section>
    </section>
  </AdminLayout>
</template>
