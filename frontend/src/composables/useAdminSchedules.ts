import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { createSchedule, listSchedules, listSemesters } from '@/api/adminSchedule'
import { adminCopy } from '@/i18n/admin'
import type { AdminSchedule, AdminSemester } from '@/types/adminSchedule'

export function useAdminSchedules() {
  const router = useRouter()
  const schedules = ref<AdminSchedule[]>([])
  const semesters = ref<AdminSemester[]>([])
  const selectedSemesterId = ref<number | null>(null)
  const isLoading = ref(true)
  const error = ref<string | null>(null)

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

  async function openSchedule(id: number): Promise<void> {
    await router.push({ name: 'admin-schedule-editor', params: { id } })
  }

  return {
    schedules,
    selectedSemesterId,
    isLoading,
    error,
    semesterOptions,
    createDraft,
    openSchedule,
  }
}
