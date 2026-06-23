<script setup lang="ts">
import { computed } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import GenerationJobPanel from '@/components/molecules/GenerationJobPanel.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminSchedules } from '@/composables/useAdminSchedules'
import { useAdminI18n } from '@/composables/useI18n'

const { t } = useAdminI18n()

const {
  schedules,
  selectedSemesterId,
  isLoading,
  error,
  semesterOptions,
  generationJob,
  createDraft,
  startGeneration,
  openSchedule,
  duplicateToDraft,
} = useAdminSchedules()

const schedulesBySemester = computed(() => {
  const map = new Map<number, typeof schedules.value>()

  for (const schedule of schedules.value) {
    map.set(schedule.semesterId, [...(map.get(schedule.semesterId) ?? []), schedule])
  }

  return Array.from(map.entries())
})
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard schedule-review-page">
      <header class="admin-page-header">
        <div>
          <h1>{{ t.schedulesTitle }}</h1>
          <p>{{ t.scheduleQueue }}</p>
        </div>
      </header>
      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" :title="t.loading" />
      <template v-else>
        <div class="schedule-create">
          <AppSelect
            id="schedule-semester"
            :label="t.semester"
            :model-value="selectedSemesterId ?? ''"
            :options="semesterOptions"
            @update:model-value="selectedSemesterId = Number($event)"
          />
          <AppButton variant="primary" data-testid="create-schedule" @click="createDraft">
            {{ t.createSchedule }}
          </AppButton>
          <AppButton data-testid="generate-schedule" @click="startGeneration">
            {{ t.generateSchedule }}
          </AppButton>
        </div>
        <GenerationJobPanel
          v-if="generationJob"
          :status="generationJob.status"
          :quality-score="generationJob.qualityScore"
          :quality-status="generationJob.qualityStatus"
          :diagnostics="generationJob.diagnostics"
          :error-message="generationJob.errorMessage"
          :generated-id="generationJob.generatedScheduleId"
          :open-label="t.openGeneratedSchedule"
          testid="generation-job"
          open-testid="open-generated-schedule"
          @open="openSchedule"
        />
        <StateMessage v-if="schedules.length === 0" :title="t.noSchedules" data-testid="no-schedules" />
        <div v-else class="schedule-review-list" data-testid="schedule-list">
          <section v-for="[semesterId, semesterSchedules] in schedulesBySemester" :key="semesterId" class="schedule-review-group">
            <h2>{{ t.semester }} #{{ semesterId }}</h2>
            <article v-for="schedule in semesterSchedules" :key="schedule.id" class="review-card">
              <div>
                <strong>#{{ schedule.id }}</strong>
                <span>{{ schedule.validFrom }} - {{ schedule.validTo }}</span>
              </div>
              <StatusBadge :tone="schedule.status === 'published' ? 'info' : 'warning'">
                {{ t.scheduleStatuses[schedule.status] ?? schedule.status }}
              </StatusBadge>
              <span>{{ t.entries }}: {{ schedule.entries.length }}</span>
              <span>{{ schedule.status === 'generated' ? t.generatedDraftLabel : t.manualDraft }}</span>
              <AppButton variant="secondary" data-testid="open-schedule" @click="openSchedule(schedule.id)">
                {{ t.openSchedule }}
              </AppButton>
              <AppButton
                v-if="schedule.status === 'published'"
                data-testid="duplicate-schedule"
                @click="duplicateToDraft(schedule.id)"
              >
                {{ t.duplicateSchedule }}
              </AppButton>
            </article>
          </section>
        </div>
      </template>
    </section>
  </AdminLayout>
</template>
