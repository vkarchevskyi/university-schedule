<script setup lang="ts">
import { computed } from 'vue'

import { useAdminI18n, usePublicScheduleI18n } from '@/composables/useI18n'
import type {
  AdminScheduleEntry,
  AdminGroup,
  AdminRoom,
  AdminSubject,
  AdminTeacher,
  AdminTimeSlot,
  LessonCard,
  WeekParity,
} from '@/types/adminSchedule'

const props = defineProps<{
  entries: AdminScheduleEntry[]
  groups: AdminGroup[]
  rooms: AdminRoom[]
  subjects: AdminSubject[]
  teachers: AdminTeacher[]
  timeSlots: AdminTimeSlot[]
  conflictEntryIds?: number[]
}>()

const emit = defineEmits<{
  place: [payload: { card: LessonCard; dayOfWeek: number; timeSlotId: number }]
  move: [payload: { entry: AdminScheduleEntry; dayOfWeek: number; timeSlotId: number }]
  select: [entry: AdminScheduleEntry]
}>()

const { t } = useAdminI18n()
const { t: publicLabels } = usePublicScheduleI18n()

const entriesByCell = computed(() => {
  const map = new Map<string, AdminScheduleEntry[]>()

  for (const entry of props.entries) {
    const key = cellKey(entry.dayOfWeek, entry.timeSlotId)
    const entries = map.get(key) ?? []
    entries.push(entry)
    map.set(key, entries)
  }

  return map
})

function entriesFor(dayOfWeek: number, timeSlotId: number): AdminScheduleEntry[] {
  return entriesByCell.value.get(cellKey(dayOfWeek, timeSlotId)) ?? []
}

function drop(event: DragEvent, dayOfWeek: number, timeSlotId: number): void {
  const rawEntry = event.dataTransfer?.getData('application/x-schedule-entry')
  if (rawEntry) {
    emit('move', { entry: JSON.parse(rawEntry) as AdminScheduleEntry, dayOfWeek, timeSlotId })
    return
  }

  const rawCard = event.dataTransfer?.getData('application/json')
  if (!rawCard) {
    return
  }

  emit('place', { card: JSON.parse(rawCard) as LessonCard, dayOfWeek, timeSlotId })
}

function dragEntry(event: DragEvent, entry: AdminScheduleEntry): void {
  event.dataTransfer?.setData('application/x-schedule-entry', JSON.stringify(entry))
}

function entryTitle(entry: AdminScheduleEntry): string {
  return subjectName(entry.subjectId)
}

function subjectName(id: number): string {
  return props.subjects.find((subject) => subject.id === id)?.name ?? `#${id}`
}

function teacherName(id: number): string {
  const teacher = props.teachers.find((item) => item.id === id)
  return teacher === undefined ? `#${id}` : `${teacher.firstName} ${teacher.lastName}`
}

function roomName(id: number): string {
  return props.rooms.find((room) => room.id === id)?.name ?? `#${id}`
}

function groupNames(ids: number[]): string {
  return ids
    .map((id) => props.groups.find((group) => group.id === id)?.name ?? `#${id}`)
    .join(', ')
}

function hasConflict(entry: AdminScheduleEntry): boolean {
  return props.conflictEntryIds?.includes(entry.id) ?? false
}

function weekParityLabel(value: WeekParity): string {
  return t.value.weekParityOptions[value]
}

function cellKey(dayOfWeek: number, timeSlotId: number): string {
  return `${dayOfWeek}-${timeSlotId}`
}
</script>

<template>
  <div class="editor-grid" data-testid="schedule-editor-grid">
    <table class="schedule-grid">
      <thead>
        <tr>
          <th class="schedule-grid__time-column" scope="col"></th>
          <th v-for="(day, index) in publicLabels.days" :key="day" scope="col">
            <span>{{ day }}</span>
            <small>{{ index + 1 }}</small>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="slot in timeSlots" :key="slot.id">
          <th scope="row">{{ slot.startsAt }}-{{ slot.endsAt }}</th>
          <td
            v-for="day in 7"
            :key="`${slot.id}-${day}`"
            data-testid="schedule-cell"
            @dragover.prevent
            @drop="drop($event, day, slot.id)"
          >
            <button
              v-for="entry in entriesFor(day, slot.id)"
              :key="entry.id"
              type="button"
              :class="['editor-entry', { 'editor-entry--conflict': hasConflict(entry) }]"
              data-testid="schedule-entry"
              draggable="true"
              @dragstart="dragEntry($event, entry)"
              @click="emit('select', entry)"
            >
              <strong>{{ entryTitle(entry) }}</strong>
              <span>{{ publicLabels.lessonTypes[entry.lessonType] ?? entry.lessonType }} · {{ weekParityLabel(entry.weekParity) }}</span>
              <small>{{ teacherName(entry.teacherId) }}</small>
              <small>{{ groupNames(entry.groupIds) }} · {{ roomName(entry.roomId) }}</small>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
