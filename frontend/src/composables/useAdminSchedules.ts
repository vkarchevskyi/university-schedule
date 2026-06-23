import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { createSchedule, duplicateSchedule, listSchedules, listSemesters } from '@/api/adminSchedule'
import { useAdminI18n } from '@/composables/useI18n'
import { useScheduleGenerationJob } from '@/composables/useScheduleGenerationJob'
import type { AdminSchedule, AdminSemester } from '@/types/adminSchedule'

export function useAdminSchedules() {
  const router = useRouter()
  const { t } = useAdminI18n()
  const schedules = ref<AdminSchedule[]>([])
  const semesters = ref<AdminSemester[]>([])
  const selectedSemesterId = ref<number | null>(null)
  const isLoading = ref(true)
  const error = ref<string | null>(null)
  const { generationJob, startGeneration: runGeneration } = useScheduleGenerationJob()

  const semesterOptions = computed(() =>
    semesters.value.map((semester) => ({
      id: semester.id,
      label: `${t.value.semester} ${semester.number}`,
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

    await runGeneration(selectedSemesterId.value, undefined, async () => {
      await load()
    })
  }

  async function openSchedule(id: number): Promise<void> {
    await router.push({ name: 'admin-schedule-editor', params: { id } })
  }

  async function duplicateToDraft(scheduleId: number): Promise<void> {
    const draft = await duplicateSchedule(scheduleId)
    await router.push({ name: 'admin-schedule-editor', params: { id: draft.id } })
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
    duplicateToDraft,
  }
}
