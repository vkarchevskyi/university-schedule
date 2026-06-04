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
  WeekParity,
} from '@/types/adminSchedule'

interface PendingPlacement {
  card: LessonCard
  dayOfWeek: number
  timeSlotId: number
  roomOptions: LookupOption[]
  selectedRoomId: number
}

export function useAdminScheduleEditor(scheduleId: number) {
  const { t } = useAdminI18n()
  const schedule = ref<AdminSchedule | null>(null)
  const cards = ref<LessonCard[]>([])
  const rooms = ref<AdminRoom[]>([])
  const groups = ref<AdminGroup[]>([])
  const teachers = ref<AdminTeacher[]>([])
  const subjects = ref<AdminSubject[]>([])
  const timeSlots = ref<AdminTimeSlot[]>([])
  const selectedGroupId = ref<number | null>(null)
  const selectedTeacherId = ref<number | null>(null)
  const selectedSubjectId = ref<number | null>(null)
  const selectedLessonType = ref('')
  const cardsSearch = ref('')
  const hideCompletedCards = ref(true)
  const requiresComputerRoomOnly = ref(false)
  const selectedEntry = ref<AdminScheduleEntry | null>(null)
  const pendingPlacement = ref<PendingPlacement | null>(null)
  const conflicts = ref<ScheduleValidationConflict[]>([])
  const entryErrors = ref<Record<string, string>>({})
  const errorEntryIds = ref<number[]>([])
  const message = ref<string | null>(null)
  const lastValidationState = ref<'passed' | 'failed' | null>(null)
  const error = ref<string | null>(null)
  const actionError = ref<{ title: string; message: string | null; details: string[] }>({
    title: '',
    message: null,
    details: [],
  })
  const isLoading = ref(true)

  const isReadOnly = computed(() => schedule.value?.status === 'published')
  const remainingLessonCount = computed(() =>
    cards.value.reduce((total, card) => total + Math.max(card.remainingLessonCount, 0), 0),
  )
  const scheduledLessonCount = computed(() =>
    cards.value.reduce((total, card) => total + Math.max(card.scheduledLessonCount, 0), 0),
  )
  const requiredLessonCount = computed(() =>
    cards.value.reduce((total, card) => total + Math.max(card.requiredLessonCount, 0), 0),
  )
  const conflictCount = computed(() => conflicts.value.length)
  const canPublish = computed(
    () =>
      !isReadOnly.value &&
      lastValidationState.value === 'passed' &&
      conflictCount.value === 0 &&
      remainingLessonCount.value === 0,
  )
  const publishReadinessLabel = computed(() => {
    if (isReadOnly.value) {
      return t.value.published
    }

    if (conflictCount.value > 0 || lastValidationState.value === 'failed') {
      return t.value.publishBlocked
    }

    if (lastValidationState.value !== 'passed') {
      return t.value.needsValidation
    }

    return remainingLessonCount.value === 0 ? t.value.readyToPublish : t.value.remainingLessons
  })

  const pendingRoomOptions = computed(() => pendingPlacement.value?.roomOptions ?? [])
  const groupOptions = computed<LookupOption[]>(() =>
    groups.value.map((group) => ({
      id: group.id,
      label: group.name,
      description: '',
    })),
  )
  const teacherOptions = computed<LookupOption[]>(() =>
    teachers.value.map((teacher) => ({
      id: teacher.id,
      label: `${teacher.firstName} ${teacher.lastName}`,
      description: '',
    })),
  )
  const subjectOptions = computed<LookupOption[]>(() =>
    subjects.value.map((subject) => ({ id: subject.id, label: subject.name, description: '' })),
  )
  const filteredCards = computed(() =>
    cards.value
      .filter((card) => selectedGroupId.value === null || card.group.id === selectedGroupId.value)
      .filter((card) => selectedTeacherId.value === null || card.teacher.id === selectedTeacherId.value)
      .filter((card) => selectedSubjectId.value === null || card.subject.id === selectedSubjectId.value)
      .filter((card) => selectedLessonType.value === '' || card.lessonType === selectedLessonType.value)
      .filter((card) => !hideCompletedCards.value || card.remainingLessonCount > 0)
      .filter((card) => !requiresComputerRoomOnly.value || card.requiresComputerRoom)
      .filter((card) => {
        const query = cardsSearch.value.trim().toLocaleLowerCase()

        if (query === '') {
          return true
        }

        return [
          card.subject.name,
          card.group.name,
          `${card.teacher.firstName} ${card.teacher.lastName}`,
          card.lessonType,
        ]
          .join(' ')
          .toLocaleLowerCase()
          .includes(query)
      })
      .sort(
        (left, right) =>
          right.remainingLessonCount - left.remainingLessonCount ||
          left.group.name.localeCompare(right.group.name) ||
          left.subject.name.localeCompare(right.subject.name),
      ),
  )
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

    clearEntryErrors()
    const roomOptions = availableRoomOptions(
      payload.card.requiresComputerRoom,
      payload.dayOfWeek,
      payload.timeSlotId,
      'both',
    )

    if (roomOptions.length === 0) {
      showActionError(t.value.noAvailableRooms)
      return
    }

    const firstRoom = roomOptions[0]
    if (firstRoom === undefined) {
      return
    }

    pendingPlacement.value = {
      ...payload,
      roomOptions,
      selectedRoomId: firstRoom.id,
    }
  }

  async function confirmPlacement(): Promise<void> {
    if (pendingPlacement.value === null || isReadOnly.value) {
      return
    }

    const placement = pendingPlacement.value
    clearEntryErrors()

    try {
      await createScheduleEntry(
        scheduleId,
        entryPayload(
          placement.card,
          placement.dayOfWeek,
          placement.timeSlotId,
          placement.selectedRoomId,
        ),
      )
      pendingPlacement.value = null
      await refreshScheduleData()
    } catch (exception) {
      handleEntryMutationError(exception)
    }
  }

  function cancelPlacement(): void {
    pendingPlacement.value = null
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

  async function duplicateEntry(entry: AdminScheduleEntry): Promise<void> {
    await createEntry({
      teachingLoadIds: entry.teachingLoadIds,
      subjectId: entry.subjectId,
      teacherId: entry.teacherId,
      lessonType: entry.lessonType,
      roomId: entry.roomId,
      timeSlotId: entry.timeSlotId,
      dayOfWeek: entry.dayOfWeek,
      weekParity: entry.weekParity,
      groupIds: entry.groupIds,
    })
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

    const currentRoomIsAvailable = availableRoomOptions(
      false,
      dayOfWeek,
      timeSlotId,
      entry.weekParity,
      entry,
    ).some((room) => room.id === entry.roomId)

    if (!currentRoomIsAvailable) {
      showActionError(t.value.noAvailableRooms)
      return
    }

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
      lastValidationState.value = result.valid ? 'passed' : 'failed'
      message.value = result.valid ? t.value.validationPassed : t.value.validationFailed
    } catch (exception) {
      handleActionError(exception)
    }
  }

  async function publish(): Promise<void> {
    try {
      const result = await validateSchedule(scheduleId)
      conflicts.value = result.conflicts
      lastValidationState.value = result.valid ? 'passed' : 'failed'

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
    roomId: number,
  ): ScheduleEntryPayload {
    return {
      teachingLoadIds: [card.teachingLoadId],
      subjectId: card.subject.id,
      teacherId: card.teacher.id,
      lessonType: card.lessonType,
      roomId,
      timeSlotId,
      dayOfWeek,
      weekParity: 'both',
      groupIds: [card.group.id],
    }
  }

  function availableRoomOptions(
    requiresComputerRoom: boolean,
    dayOfWeek: number,
    timeSlotId: number,
    weekParity: WeekParity,
    ignoredEntry: AdminScheduleEntry | null = null,
  ): LookupOption[] {
    return rooms.value
      .filter((room) => !requiresComputerRoom || room.type === 'computer')
      .filter(
        (room) =>
          !isRoomOccupied(room.id, dayOfWeek, timeSlotId, weekParity, ignoredEntry),
      )
      .map(roomOption)
  }

  function roomOption(room: AdminRoom): LookupOption {
    return {
      id: room.id,
      label: room.name,
      description: `${room.type}, ${room.capacity}`,
    }
  }

  function isRoomOccupied(
    roomId: number,
    dayOfWeek: number,
    timeSlotId: number,
    weekParity: WeekParity,
    ignoredEntry: AdminScheduleEntry | null,
  ): boolean {
    const targetSlot = timeSlots.value.find((slot) => slot.id === timeSlotId)
    if (schedule.value === null || targetSlot === undefined) {
      return true
    }

    return schedule.value.entries.some((entry) => {
      if (ignoredEntry !== null && entry.id === ignoredEntry.id) {
        return false
      }

      if (entry.roomId !== roomId || entry.dayOfWeek !== dayOfWeek) {
        return false
      }

      const entrySlot = timeSlots.value.find((slot) => slot.id === entry.timeSlotId)
      return (
        entrySlot !== undefined &&
        weekParityOverlaps(entry.weekParity, weekParity) &&
        timeRangesOverlap(entrySlot, targetSlot)
      )
    })
  }

  function weekParityOverlaps(left: WeekParity, right: WeekParity): boolean {
    return left === 'both' || right === 'both' || left === right
  }

  function timeRangesOverlap(left: AdminTimeSlot, right: AdminTimeSlot): boolean {
    return left.startsAt < right.endsAt && right.startsAt < left.endsAt
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
    selectedGroupId,
    selectedTeacherId,
    selectedSubjectId,
    selectedLessonType,
    cardsSearch,
    hideCompletedCards,
    requiresComputerRoomOnly,
    selectedEntry,
    pendingPlacement,
    conflicts,
    entryErrors,
    errorEntryIds,
    message,
    error,
    actionError,
    isLoading,
    isReadOnly,
    remainingLessonCount,
    scheduledLessonCount,
    requiredLessonCount,
    conflictCount,
    canPublish,
    publishReadinessLabel,
    pendingRoomOptions,
    groupOptions,
    teacherOptions,
    subjectOptions,
    filteredCards,
    filteredEntries,
    place,
    confirmPlacement,
    cancelPlacement,
    createEntry,
    moveEntry,
    saveEntry,
    removeEntry,
    duplicateEntry,
    validate,
    publish,
    clearActionError,
  }
}
