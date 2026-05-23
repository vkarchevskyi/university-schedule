import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import {
  createSchedule,
  generateSchedule,
  getGenerationJob,
  listSchedules,
  listSemesters,
} from '@/api/adminSchedule'
import { adminCopy } from '@/i18n/admin'
import type { AdminSchedule, AdminSemester, ScheduleGenerationJob } from '@/types/adminSchedule'

export function useAdminSchedules() {
  const router = useRouter()
  const schedules = ref<AdminSchedule[]>([])
  const semesters = ref<AdminSemester[]>([])
  const selectedSemesterId = ref<number | null>(null)
  const isLoading = ref(true)
  const error = ref<string | null>(null)
  const generationJob = ref<ScheduleGenerationJob | null>(null)

  const semesterOptions = computed(() =>
    semesters.value.map((semester) => ({
      id: semester.id,
      label: `${adminCopy.semester} ${semester.number}`,
      description: `${semester.startsAt} - ${semester.endsAt}`,
    })),
  )

  onMounted(load)

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
      error.value = adminCopy.apiError
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
    await pollGeneration(generationJob.value.id)
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
