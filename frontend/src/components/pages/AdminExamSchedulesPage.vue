<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import ConfirmActionButton from '@/components/molecules/ConfirmActionButton.vue'
import GenerationJobPanel from '@/components/molecules/GenerationJobPanel.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminExamSchedules } from '@/composables/useAdminExamSchedules'
import { useAdminI18n } from '@/composables/useI18n'

const { t } = useAdminI18n()

const {
  schedules,
  generationJob,
  isLoading,
  error,
  createDraft,
  startGeneration,
  openSchedule,
  removeSchedule,
} = useAdminExamSchedules()
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard">
      <header class="admin-page-header">
        <div>
          <h1>{{ t.examSchedulesTitle }}</h1>
          <p>{{ t.examSchedulesIntro }}</p>
        </div>
        <div class="header-actions">
          <AppButton variant="primary" data-testid="create-exam-schedule" @click="createDraft">
            {{ t.createExamSchedule }}
          </AppButton>
          <AppButton data-testid="generate-exam-schedule" @click="startGeneration">
            {{ t.generateExamSchedule }}
          </AppButton>
        </div>
      </header>

      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" :title="t.loading" />
      <GenerationJobPanel
        v-if="generationJob"
        :status="generationJob.status"
        :quality-score="generationJob.qualityScore"
        :quality-status="generationJob.qualityStatus"
        :diagnostics="generationJob.diagnostics"
        :error-message="generationJob.errorMessage"
        :generated-id="generationJob.generatedExamScheduleId"
        :open-label="t.openGeneratedSchedule"
        testid="exam-generation-job"
        open-testid="open-generated-exam-schedule"
        @open="openSchedule"
      />

      <div v-if="!isLoading" class="schedule-review-list" data-testid="exam-schedule-list">
        <StateMessage v-if="schedules.length === 0" :title="t.noSchedules" />
        <article v-for="schedule in schedules" v-else :key="schedule.id" class="review-card">
          <div>
            <strong>#{{ schedule.id }}</strong>
            <span>{{ t.semester }} #{{ schedule.semesterId }}</span>
          </div>
          <StatusBadge :tone="schedule.status === 'published' ? 'info' : 'warning'">
            {{ schedule.status }}
          </StatusBadge>
          <span>{{ t.entries }}: {{ schedule.entries.length }}</span>
          <div class="table-actions">
            <AppButton @click="openSchedule(schedule.id)">{{ t.openSchedule }}</AppButton>
            <ConfirmActionButton :message="t.deleteConfirm" @confirm="removeSchedule(schedule.id)">
              {{ t.delete }}
            </ConfirmActionButton>
          </div>
        </article>
      </div>
    </section>
  </AdminLayout>
</template>
