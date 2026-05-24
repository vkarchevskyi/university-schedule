import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { subscribeToGenerationJob } from '@/api/generationNotifications'
import {
  createExamSchedule,
  deleteExamSchedule,
  generateExamSchedule,
  getExamGenerationJob,
  listExamSchedules,
} from '@/api/adminExamSchedule'
import { listSemesters } from '@/api/adminSchedule'
import { useAdminI18n } from '@/composables/useI18n'
import type { AdminSemester } from '@/types/adminSchedule'
import type { ExamGenerationJob, ExamSchedule } from '@/types/adminExamSchedule'

export function useAdminExamSchedules() {
  const router = useRouter()
  const { t } = useAdminI18n()
  const schedules = ref<ExamSchedule[]>([])
  const semesters = ref<AdminSemester[]>([])
  const selectedSemesterId = ref<number | null>(null)
  const generationJob = ref<ExamGenerationJob | null>(null)
  const isLoading = ref(true)
  const error = ref<string | null>(null)
  let stopGenerationNotifications: (() => void) | null = null

  onMounted(load)
  onUnmounted(() => stopGenerationNotifications?.())

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const [semesterResponse, scheduleResponse] = await Promise.all([
        listSemesters(),
        listExamSchedules(),
      ])
      semesters.value = semesterResponse.items
      schedules.value = scheduleResponse.items
      selectedSemesterId.value = semesterResponse.items[0]?.id ?? null
    } catch {
      error.value = t.value.apiError
    } finally {
      isLoading.value = false
    }
  }

  async function createDraft(): Promise<void> {
    if (selectedSemesterId.value === null) {
      return
    }

    const schedule = await createExamSchedule(selectedSemesterId.value)
    await router.push({ name: 'admin-exam-schedule-editor', params: { id: schedule.id } })
  }

  async function startGeneration(): Promise<void> {
    if (selectedSemesterId.value === null) {
      return
    }

    generationJob.value = await generateExamSchedule(selectedSemesterId.value)
    await waitForGeneration(generationJob.value.id)
  }

  async function waitForGeneration(jobId: string): Promise<void> {
    await new Promise<void>((resolve) => {
      let resolved = false
      const fallbackTimeout = window.setTimeout(startPollingFallback, 10000)

      function finish(): void {
        if (resolved) {
          return
        }
        resolved = true
        window.clearTimeout(fallbackTimeout)
        stopGenerationNotifications?.()
        stopGenerationNotifications = null
        resolve()
      }

      async function startPollingFallback(): Promise<void> {
        if (resolved) {
          return
        }
        await pollGeneration(jobId)
        finish()
      }

      stopGenerationNotifications = subscribeToGenerationJob<ExamGenerationJob>(
        'exam_schedule_generation_job',
        jobId,
        async ({ job }) => {
          generationJob.value = job

          if (job.status === 'completed' || job.status === 'failed') {
            await load()
            finish()
          }
        },
        startPollingFallback,
      )
    })
  }

  async function pollGeneration(jobId: string): Promise<void> {
    for (let attempt = 0; attempt < 10; attempt += 1) {
      const job = await getExamGenerationJob(jobId)
      generationJob.value = job

      if (job.status === 'completed' || job.status === 'failed') {
        await load()
        return
      }

      await new Promise((resolve) => window.setTimeout(resolve, 1000))
    }
  }

  async function openSchedule(id: number): Promise<void> {
    await router.push({ name: 'admin-exam-schedule-editor', params: { id } })
  }

  async function removeSchedule(id: number): Promise<void> {
    await deleteExamSchedule(id)
    await load()
  }

  return {
    schedules,
    semesters,
    selectedSemesterId,
    generationJob,
    isLoading,
    error,
    createDraft,
    startGeneration,
    openSchedule,
    removeSchedule,
  }
}
