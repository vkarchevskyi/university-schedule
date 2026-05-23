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
import { adminCopy } from '@/i18n/admin'
import type {
  AdminRoom,
  AdminSchedule,
  AdminScheduleEntry,
  AdminGroup,
  AdminSubject,
  AdminTeacher,
  AdminTimeSlot,
  LessonCard,
  ScheduleEntryPayload,
  ScheduleValidationConflict,
} from '@/types/adminSchedule'

export function useAdminScheduleEditor(scheduleId: number) {
  const schedule = ref<AdminSchedule | null>(null)
  const cards = ref<LessonCard[]>([])
  const rooms = ref<AdminRoom[]>([])
  const groups = ref<AdminGroup[]>([])
  const teachers = ref<AdminTeacher[]>([])
  const subjects = ref<AdminSubject[]>([])
  const timeSlots = ref<AdminTimeSlot[]>([])
  const selectedRoomId = ref<number | null>(null)
  const selectedEntry = ref<AdminScheduleEntry | null>(null)
  const conflicts = ref<ScheduleValidationConflict[]>([])
  const message = ref<string | null>(null)
  const error = ref<string | null>(null)
  const isLoading = ref(true)

  const roomOptions = computed(() =>
    rooms.value.map((room) => ({
      id: room.id,
      label: room.name,
      description: `${room.type}, ${room.capacity}`,
    })),
  )

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
    } catch {
      error.value = adminCopy.apiError
    } finally {
      isLoading.value = false
    }
  }

  async function refreshScheduleData(): Promise<void> {
    error.value = null

    try {
      const [scheduleResponse, cardsResponse] = await Promise.all([
        getSchedule(scheduleId),
        listLessonCards(scheduleId),
      ])
      schedule.value = scheduleResponse
      cards.value = cardsResponse.items
    } catch {
      error.value = adminCopy.apiError
    }
  }

  async function place(payload: {
    card: LessonCard
    dayOfWeek: number
    timeSlotId: number
  }): Promise<void> {
    if (selectedRoomId.value === null) {
      error.value = adminCopy.selectRoom
      return
    }

    await createScheduleEntry(
      scheduleId,
      entryPayload(payload.card, payload.dayOfWeek, payload.timeSlotId),
    )
    await refreshScheduleData()
  }

  async function saveEntry(payload: Partial<ScheduleEntryPayload>): Promise<void> {
    if (selectedEntry.value === null) {
      return
    }

    await updateScheduleEntry(scheduleId, selectedEntry.value.id, payload)
    selectedEntry.value = null
    await refreshScheduleData()
  }

  async function createEntry(payload: ScheduleEntryPayload): Promise<void> {
    await createScheduleEntry(scheduleId, payload)
    await refreshScheduleData()
  }

  async function moveEntry(entry: AdminScheduleEntry, dayOfWeek: number, timeSlotId: number): Promise<void> {
    await updateScheduleEntry(scheduleId, entry.id, { dayOfWeek, timeSlotId })
    await refreshScheduleData()
  }

  async function removeEntry(): Promise<void> {
    if (selectedEntry.value === null) {
      return
    }

    await deleteScheduleEntry(scheduleId, selectedEntry.value.id)
    selectedEntry.value = null
    await refreshScheduleData()
  }

  async function validate(): Promise<void> {
    const result = await validateSchedule(scheduleId)
    conflicts.value = result.conflicts
    message.value = result.valid ? adminCopy.validationPassed : adminCopy.validationFailed
  }

  async function publish(): Promise<void> {
    const result = await validateSchedule(scheduleId)
    conflicts.value = result.conflicts

    if (!result.valid) {
      message.value = adminCopy.cannotPublishInvalid
      return
    }

    schedule.value = await publishSchedule(scheduleId)
    message.value = adminCopy.published
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

  return {
    schedule,
    cards,
    rooms,
    groups,
    teachers,
    subjects,
    timeSlots,
    selectedRoomId,
    selectedEntry,
    conflicts,
    message,
    error,
    isLoading,
    roomOptions,
    place,
    createEntry,
    moveEntry,
    saveEntry,
    removeEntry,
    validate,
    publish,
  }
}
