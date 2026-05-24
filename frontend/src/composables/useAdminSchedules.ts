import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { subscribeToGenerationJob } from '@/api/generationNotifications'
import {
  createSchedule,
  generateSchedule,
  getGenerationJob,
  listSchedules,
  listSemesters,
} from '@/api/adminSchedule'
import { useAdminI18n } from '@/composables/useI18n'
import type { AdminSchedule, AdminSemester, ScheduleGenerationJob } from '@/types/adminSchedule'

export function useAdminSchedules() {
  const router = useRouter()
  const { t } = useAdminI18n()
  const schedules = ref<AdminSchedule[]>([])
  const semesters = ref<AdminSemester[]>([])
  const selectedSemesterId = ref<number | null>(null)
  const isLoading = ref(true)
  const error = ref<string | null>(null)
  const generationJob = ref<ScheduleGenerationJob | null>(null)
  let stopGenerationNotifications: (() => void) | null = null

  const semesterOptions = computed(() =>
    semesters.value.map((semester) => ({
      id: semester.id,
      label: `${t.value.semester} ${semester.number}`,
      description: `${semester.startsAt} - ${semester.endsAt}`,
    })),
  )

  onMounted(load)
  onUnmounted(() => stopGenerationNotifications?.())

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const [semesterResponse, scheduleResponse] = await Promise.all([
        listSemesters(),
        listSchedules(),
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
    const semester = semesters.value.find((item) => item.id === selectedSemesterId.value)
    if (!semester) {
      return
    }

    const schedule = await createSchedule({
      semesterId: semester.id,
      validFrom: semester.startsAt,
      validTo: semester.endsAt,
    })

    await router.push({ name: 'admin-schedule-editor', params: { id: schedule.id } })
  }

  async function startGeneration(): Promise<void> {
    if (selectedSemesterId.value === null) {
      return
    }

    generationJob.value = await generateSchedule(selectedSemesterId.value)
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

      stopGenerationNotifications = subscribeToGenerationJob<ScheduleGenerationJob>(
        'schedule_generation_job',
        jobId,
        async ({ job }) => {
          generationJob.value = job

          if (job.status === 'completed' || job.status === 'failed') {
            if (job.generatedScheduleId !== null) {
              await load()
            }
            finish()
          }
        },
        startPollingFallback,
      )
    })
  }

  async function pollGeneration(jobId: string): Promise<void> {
    const terminal = new Set(['completed', 'failed'])

    for (let attempt = 0; attempt < 10; attempt += 1) {
      const job = await getGenerationJob(jobId)
      generationJob.value = job

      if (terminal.has(job.status)) {
        if (job.generatedScheduleId !== null) {
          await load()
        }
        return
      }

      await new Promise((resolve) => window.setTimeout(resolve, 1000))
    }
  }

  async function openSchedule(id: number): Promise<void> {
    await router.push({ name: 'admin-schedule-editor', params: { id } })
  }

  return {
    schedules,
    selectedSemesterId,
    isLoading,
    error,
    generationJob,
    semesterOptions,
    createDraft,
    startGeneration,
    openSchedule,
  }
}
