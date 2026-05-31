import { computed, onMounted, ref } from 'vue'

import {
  createScheduleEntry,
  deleteScheduleEntry,
  getSchedule,
  listGroups,
  listLessonCards,
  listRooms,
  listSubjects,
  listTeachers,
  listTimeSlots,
  publishSchedule,
  updateScheduleEntry,
  validateSchedule,
} from '@/api/adminSchedule'
import { ApiError } from '@/api/http'
import { useAdminI18n } from '@/composables/useI18n'
import type {
  AdminRoom,
  AdminSchedule,
  AdminScheduleEntry,
  AdminGroup,
  AdminSubject,
  AdminTeacher,
  AdminTimeSlot,
  LessonCard,
  LookupOption,
  ScheduleEntryPayload,
  ScheduleValidationConflict,
} from '@/types/adminSchedule'

export function useAdminScheduleEditor(scheduleId: number) {
  const { t } = useAdminI18n()
  const schedule = ref<AdminSchedule | null>(null)
  const cards = ref<LessonCard[]>([])
  const rooms = ref<AdminRoom[]>([])
  const groups = ref<AdminGroup[]>([])
  const teachers = ref<AdminTeacher[]>([])
  const subjects = ref<AdminSubject[]>([])
  const timeSlots = ref<AdminTimeSlot[]>([])
  const selectedRoomId = ref<number | null>(null)
  const selectedGroupId = ref<number | null>(null)
  const selectedEntry = ref<AdminScheduleEntry | null>(null)
  const conflicts = ref<ScheduleValidationConflict[]>([])
  const entryErrors = ref<Record<string, string>>({})
  const errorEntryIds = ref<number[]>([])
  const message = ref<string | null>(null)
  const error = ref<string | null>(null)
  const actionError = ref<{ title: string; message: string | null; details: string[] }>({
    title: '',
    message: null,
    details: [],
  })
  const isLoading = ref(true)

  const isReadOnly = computed(() => schedule.value?.status === 'published')

  const roomOptions = computed(() =>
    rooms.value.map((room) => ({
      id: room.id,
      label: room.name,
      description: `${room.type}, ${room.capacity}`,
    })),
  )
  const groupOptions = computed<LookupOption[]>(() =>
    groups.value.map((group) => ({
      id: group.id,
      label: group.name,
      description: `${group.speciality}, ${group.course}`,
    })),
  )
  const filteredCards = computed(() => {
    if (selectedGroupId.value === null) {
      return cards.value
    }

    return cards.value.filter((card) => card.group.id === selectedGroupId.value)
  })
  const filteredEntries = computed(() => {
    if (selectedGroupId.value === null || schedule.value === null) {
      return schedule.value?.entries ?? []
    }

    return schedule.value.entries.filter((entry) =>
      entry.groupIds.includes(selectedGroupId.value as number),
    )
  })

  onMounted(loadEditor)

  async function loadEditor(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const [
        scheduleResponse,
        cardsResponse,
        roomResponse,
        slotResponse,
        groupResponse,
        teacherResponse,
        subjectResponse,
      ] = await Promise.all([
        getSchedule(scheduleId),
        listLessonCards(scheduleId),
        listRooms(),
        listTimeSlots(),
        listGroups(),
        listTeachers(),
        listSubjects(),
      ])
      schedule.value = scheduleResponse
      cards.value = cardsResponse.items
      rooms.value = roomResponse.items
      timeSlots.value = slotResponse.items
      groups.value = groupResponse.items
      teachers.value = teacherResponse.items
      subjects.value = subjectResponse.items
      selectedRoomId.value = roomResponse.items[0]?.id ?? null
      ensureSelectedGroup()
      clearEntryErrors()
    } catch {
      error.value = t.value.apiError
    } finally {
      isLoading.value = false
    }
  }

  async function refreshScheduleData(): Promise<void> {
    actionError.value = emptyActionError()

    try {
      const [scheduleResponse, cardsResponse] = await Promise.all([
        getSchedule(scheduleId),
        listLessonCards(scheduleId),
      ])
      schedule.value = scheduleResponse
      cards.value = cardsResponse.items
      ensureSelectedGroup()
      clearEntryErrors()
    } catch {
      showActionError()
    }
  }

  async function place(payload: {
    card: LessonCard
    dayOfWeek: number
    timeSlotId: number
  }): Promise<void> {
    if (isReadOnly.value) {
      return
    }

    if (selectedRoomId.value === null) {
      clearEntryErrors()
      showActionError(t.value.selectRoom)
      return
    }

    clearEntryErrors()

    try {
      await createScheduleEntry(
        scheduleId,
        entryPayload(payload.card, payload.dayOfWeek, payload.timeSlotId),
      )
      await refreshScheduleData()
    } catch (exception) {
      handleEntryMutationError(exception)
    }
  }

  async function saveEntry(payload: Partial<ScheduleEntryPayload>): Promise<void> {
    if (selectedEntry.value === null || isReadOnly.value) {
      return
    }

    clearEntryErrors()
    const entryId = selectedEntry.value.id

    try {
      await updateScheduleEntry(scheduleId, entryId, payload)
      selectedEntry.value = null
      await refreshScheduleData()
    } catch (exception) {
      handleEntryMutationError(exception, [entryId])
    }
  }

  async function createEntry(payload: ScheduleEntryPayload): Promise<void> {
    if (isReadOnly.value) {
      return
    }

    clearEntryErrors()

    try {
      await createScheduleEntry(scheduleId, payload)
      await refreshScheduleData()
    } catch (exception) {
      handleEntryMutationError(exception)
    }
  }

  async function moveEntry(
    entry: AdminScheduleEntry,
    dayOfWeek: number,
    timeSlotId: number,
  ): Promise<void> {
    if (isReadOnly.value) {
      return
    }

    clearEntryErrors()

    try {
      await updateScheduleEntry(scheduleId, entry.id, { dayOfWeek, timeSlotId })
      await refreshScheduleData()
    } catch (exception) {
      handleEntryMutationError(exception, [entry.id])
    }
  }

  async function removeEntry(): Promise<void> {
    if (selectedEntry.value === null || isReadOnly.value) {
      return
    }

    clearEntryErrors()
    const entryId = selectedEntry.value.id

    try {
      await deleteScheduleEntry(scheduleId, entryId)
      selectedEntry.value = null
      await refreshScheduleData()
    } catch (exception) {
      handleEntryMutationError(exception, [entryId])
    }
  }

  async function validate(): Promise<void> {
    try {
      const result = await validateSchedule(scheduleId)
      conflicts.value = result.conflicts
      message.value = result.valid ? t.value.validationPassed : t.value.validationFailed
    } catch (exception) {
      handleActionError(exception)
    }
  }

  async function publish(): Promise<void> {
    try {
      const result = await validateSchedule(scheduleId)
      conflicts.value = result.conflicts

      if (!result.valid) {
        message.value = t.value.cannotPublishInvalid
        return
      }

      schedule.value = await publishSchedule(scheduleId)
      message.value = t.value.published
    } catch (exception) {
      handleActionError(exception)
    }
  }

  function entryPayload(
    card: LessonCard,
    dayOfWeek: number,
    timeSlotId: number,
  ): ScheduleEntryPayload {
    return {
      teachingLoadIds: [card.teachingLoadId],
      subjectId: card.subject.id,
      teacherId: card.teacher.id,
      lessonType: card.lessonType,
      roomId: selectedRoomId.value as number,
      timeSlotId,
      dayOfWeek,
      weekParity: 'both',
      groupIds: [card.group.id],
    }
  }

  function clearEntryErrors(): void {
    entryErrors.value = {}
    errorEntryIds.value = []
  }

  function ensureSelectedGroup(): void {
    const availableIds = new Set([
      ...cards.value.map((card) => card.group.id),
      ...groups.value.map((group) => group.id),
    ])

    if (selectedGroupId.value !== null && availableIds.has(selectedGroupId.value)) {
      return
    }

    selectedGroupId.value = cards.value[0]?.group.id ?? groups.value[0]?.id ?? null
  }

  function handleEntryMutationError(exception: unknown, entryIds: number[] = []): void {
    if (exception instanceof ApiError && exception.violations.length > 0) {
      const fieldViolations = exception.violations.filter(
        (violation) => violation.propertyPath !== 'schedule',
      )
      const actionViolations = exception.violations.filter(
        (violation) => violation.propertyPath === 'schedule',
      )

      if (actionViolations.length > 0) {
        showActionError(
          exception.message,
          actionViolations.map((violation) => violation.message),
        )
        return
      }

      entryErrors.value = Object.fromEntries(
        fieldViolations.map((violation) => [violation.propertyPath, violation.message]),
      )
      errorEntryIds.value = entryIds
      error.value = null
      return
    }

    handleActionError(exception)
  }

  function handleActionError(exception: unknown): void {
    if (exception instanceof ApiError && exception.violations.length > 0) {
      showActionError(
        exception.message,
        exception.violations.map((violation) => violation.message),
      )
      return
    }

    if (exception instanceof Error) {
      showActionError(exception.message)
      return
    }

    showActionError()
  }

  function showActionError(message: string | null = null, details: string[] = []): void {
    actionError.value = {
      title: t.value.apiError,
      message,
      details,
    }
    error.value = null
  }

  function clearActionError(): void {
    actionError.value = emptyActionError()
  }

  function emptyActionError(): { title: string; message: string | null; details: string[] } {
    return { title: '', message: null, details: [] }
  }

  return {
    schedule,
    cards,
    rooms,
    groups,
    teachers,
    subjects,
    timeSlots,
    selectedRoomId,
    selectedGroupId,
    selectedEntry,
    conflicts,
    entryErrors,
    errorEntryIds,
    message,
    error,
    actionError,
    isLoading,
    isReadOnly,
    roomOptions,
    groupOptions,
    filteredCards,
    filteredEntries,
    place,
    createEntry,
    moveEntry,
    saveEntry,
    removeEntry,
    validate,
    publish,
    clearActionError,
  }
}
