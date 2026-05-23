import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import {
  createExamSchedule,
  generateExamSchedule,
  getExamGenerationJob,
  listExamSchedules,
} from '@/api/adminExamSchedule'
import { listSemesters } from '@/api/adminSchedule'
import type { AdminSemester } from '@/types/adminSchedule'
import type { ExamGenerationJob, ExamSchedule } from '@/types/adminExamSchedule'

export function useAdminExamSchedules() {
  const router = useRouter()
  const schedules = ref<ExamSchedule[]>([])
  const semesters = ref<AdminSemester[]>([])
  const selectedSemesterId = ref<number | null>(null)
  const generationJob = ref<ExamGenerationJob | null>(null)
  const isLoading = ref(true)
  const error = ref<string | null>(null)

  onMounted(load)

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
      error.value = 'Не вдалося завантажити іспити.'
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
    await pollGeneration(generationJob.value.id)
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
  }
}
