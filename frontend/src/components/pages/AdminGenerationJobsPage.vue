<script setup lang="ts">
import { useRouter } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminGenerationJobs } from '@/composables/useAdminGenerationJobs'
import { useAdminI18n } from '@/composables/useI18n'
import type { ExamGenerationJob } from '@/types/adminExamSchedule'
import type { ScheduleGenerationJob } from '@/types/adminSchedule'

const router = useRouter()
const { t } = useAdminI18n()
const { scheduleJobs, examJobs, isLoading, error } = useAdminGenerationJobs()

function diagnosticsText(diagnostics: Record<string, unknown> | null): string {
  if (diagnostics === null) {
    return t.value.noDiagnostics
  }

  return JSON.stringify(diagnostics)
}

function qualityText(job: ScheduleGenerationJob | ExamGenerationJob): string {
  if (job.qualityScore === null && job.qualityStatus === null) {
    return t.value.notAvailable
  }

  return [job.qualityScore, job.qualityStatus].filter((value) => value !== null).join(' / ')
}

async function openSchedule(id: number): Promise<void> {
  await router.push({ name: 'admin-schedule-editor', params: { id } })
}

async function openExamSchedule(id: number): Promise<void> {
  await router.push({ name: 'admin-exam-schedule-editor', params: { id } })
}
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard generation-jobs-page">
      <h1>{{ t.generationJobsTitle }}</h1>
      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" :title="t.loading" />
      <template v-else>
        <section class="generation-jobs-section">
          <h2>{{ t.scheduleGenerationJobs }}</h2>
          <StateMessage
            v-if="scheduleJobs.length === 0"
            :title="t.noGenerationJobs"
            data-testid="no-schedule-generation-jobs"
          />
          <div v-else class="admin-table-wrap">
            <table class="admin-table" data-testid="schedule-generation-job-table">
              <thead>
                <tr>
                  <th>{{ t.date }}</th>
                  <th>{{ t.type }}</th>
                  <th>{{ t.semester }}</th>
                  <th>{{ t.status }}</th>
                  <th>{{ t.quality }}</th>
                  <th>{{ t.generatedDraft }}</th>
                  <th>{{ t.diagnostics }}</th>
                  <th>{{ t.actions }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="job in scheduleJobs" :key="job.id">
                  <td>{{ job.createdAt }}</td>
                  <td>{{ t.scheduleGenerationJobType }}</td>
                  <td>#{{ job.semesterId }}</td>
                  <td>
                    <StatusBadge :tone="job.status === 'failed' ? 'warning' : 'info'">
                      {{ job.status }}
                    </StatusBadge>
                  </td>
                  <td>{{ qualityText(job) }}</td>
                  <td>{{ job.generatedScheduleId === null ? t.notAvailable : `#${job.generatedScheduleId}` }}</td>
                  <td>
                    <span v-if="job.errorMessage" class="field-error">{{ job.errorMessage }}</span>
                    <span v-else>{{ diagnosticsText(job.diagnostics) }}</span>
                  </td>
                  <td>
                    <AppButton
                      v-if="job.generatedScheduleId !== null"
                      data-testid="open-schedule-generation-result"
                      @click="openSchedule(job.generatedScheduleId)"
                    >
                      {{ t.openSchedule }}
                    </AppButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="generation-jobs-section">
          <h2>{{ t.examGenerationJobs }}</h2>
          <StateMessage
            v-if="examJobs.length === 0"
            :title="t.noGenerationJobs"
            data-testid="no-exam-generation-jobs"
          />
          <div v-else class="admin-table-wrap">
            <table class="admin-table" data-testid="exam-generation-job-table">
              <thead>
                <tr>
                  <th>{{ t.date }}</th>
                  <th>{{ t.type }}</th>
                  <th>{{ t.semester }}</th>
                  <th>{{ t.status }}</th>
                  <th>{{ t.quality }}</th>
                  <th>{{ t.generatedDraft }}</th>
                  <th>{{ t.diagnostics }}</th>
                  <th>{{ t.actions }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="job in examJobs" :key="job.id">
                  <td>{{ job.createdAt }}</td>
                  <td>{{ t.examGenerationJobType }}</td>
                  <td>#{{ job.semesterId }}</td>
                  <td>
                    <StatusBadge :tone="job.status === 'failed' ? 'warning' : 'info'">
                      {{ job.status }}
                    </StatusBadge>
                  </td>
                  <td>{{ qualityText(job) }}</td>
                  <td>
                    {{ job.generatedExamScheduleId === null ? t.notAvailable : `#${job.generatedExamScheduleId}` }}
                  </td>
                  <td>
                    <span v-if="job.errorMessage" class="field-error">{{ job.errorMessage }}</span>
                    <span v-else>{{ diagnosticsText(job.diagnostics) }}</span>
                  </td>
                  <td>
                    <AppButton
                      v-if="job.generatedExamScheduleId !== null"
                      data-testid="open-exam-generation-result"
                      @click="openExamSchedule(job.generatedExamScheduleId)"
                    >
                      {{ t.openSchedule }}
                    </AppButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </section>
  </AdminLayout>
</template>
