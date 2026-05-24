import { onMounted, ref } from 'vue'

import { listExamGenerationJobs } from '@/api/adminExamSchedule'
import { listGenerationJobs } from '@/api/adminSchedule'
import { useAdminI18n } from '@/composables/useI18n'
import type { ExamGenerationJob } from '@/types/adminExamSchedule'
import type { ScheduleGenerationJob } from '@/types/adminSchedule'

export function useAdminGenerationJobs() {
  const { t } = useAdminI18n()
  const scheduleJobs = ref<ScheduleGenerationJob[]>([])
  const examJobs = ref<ExamGenerationJob[]>([])
  const isLoading = ref(true)
  const error = ref<string | null>(null)

  onMounted(load)

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const [scheduleResponse, examResponse] = await Promise.all([
        listGenerationJobs(),
        listExamGenerationJobs(),
      ])
      scheduleJobs.value = scheduleResponse.items
      examJobs.value = examResponse.items
    } catch {
      error.value = t.value.apiError
    } finally {
      isLoading.value = false
    }
  }

  return {
    scheduleJobs,
    examJobs,
    isLoading,
    error,
    load,
  }
}
