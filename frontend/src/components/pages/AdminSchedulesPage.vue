<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
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
} = useAdminSchedules()
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard">
      <h1>{{ t.schedulesTitle }}</h1>
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
        <StateMessage
          v-if="schedules.length === 0"
          :title="t.noSchedules"
          data-testid="no-schedules"
        />
        <div v-else class="schedule-list" data-testid="schedule-list">
          <article v-for="schedule in schedules" :key="schedule.id" class="schedule-list__item">
            <div>
              <strong>#{{ schedule.id }} · {{ t.scheduleStatuses[schedule.status] ?? schedule.status }}</strong>
              <span>{{ schedule.validFrom }} - {{ schedule.validTo }}</span>
            </div>
            <AppButton
              variant="secondary"
              data-testid="open-schedule"
              @click="openSchedule(schedule.id)"
            >
              {{ t.openSchedule }}
            </AppButton>
          </article>
        </div>
      </template>
    </section>
  </AdminLayout>
</template>
