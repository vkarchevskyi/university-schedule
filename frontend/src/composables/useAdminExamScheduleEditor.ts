import { onMounted, ref } from 'vue'

import {
  createExamEntry,
  deleteExamEntry,
  getExamSchedule,
  updateExamEntry,
  validateExamSchedule,
} from '@/api/adminExamSchedule'
import { listGroups, listRooms, listSubjects, listTeachers } from '@/api/adminSchedule'
import { useAdminI18n } from '@/composables/useI18n'
import type {
  ExamConflict,
  ExamLookups,
  ExamSchedule,
  ExamScheduleEntry,
  ExamScheduleEntryPayload,
} from '@/types/adminExamSchedule'

export function useAdminExamScheduleEditor(scheduleId: number) {
  const { t } = useAdminI18n()
  const schedule = ref<ExamSchedule | null>(null)
  const lookups = ref<ExamLookups>({ groups: [], rooms: [], subjects: [], teachers: [] })
  const selectedEntry = ref<ExamScheduleEntry | null>(null)
  const conflicts = ref<ExamConflict[]>([])
  const message = ref<string | null>(null)
  const error = ref<string | null>(null)
  const isLoading = ref(true)

  onMounted(load)

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const [scheduleResponse, groups, rooms, subjects, teachers] = await Promise.all([
        getExamSchedule(scheduleId),
        listGroups(),
        listRooms(),
        listSubjects(),
        listTeachers(),
      ])
      schedule.value = scheduleResponse
      lookups.value = {
        groups: groups.items,
        rooms: rooms.items,
        subjects: subjects.items,
        teachers: teachers.items,
      }
    } catch {
      error.value = t.value.apiError
    } finally {
      isLoading.value = false
    }
  }

  async function save(payload: ExamScheduleEntryPayload): Promise<void> {
    if (selectedEntry.value === null) {
      await createExamEntry(scheduleId, payload)
    } else {
      await updateExamEntry(scheduleId, selectedEntry.value.id, payload)
    }

    selectedEntry.value = null
    await load()
  }

  async function remove(entry: ExamScheduleEntry): Promise<void> {
    await deleteExamEntry(scheduleId, entry.id)
    await load()
  }

  async function validate(): Promise<void> {
    const result = await validateExamSchedule(scheduleId)
    conflicts.value = result.conflicts
    message.value = result.valid ? t.value.examScheduleValid : t.value.validationFailed
  }

  return {
    schedule,
    lookups,
    selectedEntry,
    conflicts,
    message,
    error,
    isLoading,
    save,
    remove,
    validate,
  }
}
