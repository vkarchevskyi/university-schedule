<script setup lang="ts">
import { computed } from 'vue'

import { usePublicScheduleI18n } from '@/composables/useI18n'
import { scheduleWeekdays } from '@/utils/date'
import type {
  AdminScheduleEntry,
  AdminRoom,
  AdminSubject,
  AdminTeacher,
  AdminTimeSlot,
  LessonCard,
  LessonType,
} from '@/types/adminSchedule'

const props = defineProps<{
  entries: AdminScheduleEntry[]
  rooms: AdminRoom[]
  groups: Array<{ id: number; name: string }>
  subjects: AdminSubject[]
  teachers: AdminTeacher[]
  timeSlots: AdminTimeSlot[]
  conflictEntryIds?: number[]
  conflictMessages?: Record<number, string[]>
  dropHint: string
  conflictLabel: string
  readOnly?: boolean
}>()

const emit = defineEmits<{
  place: [payload: { card: LessonCard; dayOfWeek: number; timeSlotId: number }]
  move: [payload: { entry: AdminScheduleEntry; dayOfWeek: number; timeSlotId: number }]
  select: [entry: AdminScheduleEntry]
}>()

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
  if (props.readOnly) {
    return
  }

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
  if (props.readOnly) {
    event.preventDefault()
    return
  }

  event.dataTransfer?.setData('application/x-schedule-entry', JSON.stringify(entry))
}

function entryTitle(entry: AdminScheduleEntry): string {
  return subjectName(entry.subjectId)
}

function entryTitleWithMarker(entry: AdminScheduleEntry): string {
  return `${entryTitle(entry)} (${lessonTypeMarker(entry.lessonType)})`
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

function groupNames(entry: AdminScheduleEntry): string {
  return entry.groupIds.map((id) => props.groups.find((group) => group.id === id)?.name ?? `#${id}`).join(', ')
}

function hasConflict(entry: AdminScheduleEntry): boolean {
  return props.conflictEntryIds?.includes(entry.id) ?? false
}

function lessonTypeMarker(type: LessonType): string {
  const isEnglish = publicLabels.value.lessonTypes.lecture === 'Lecture'

  if (isEnglish) {
    return (
      {
        lecture: 'lec',
        laboratory: 'lab',
        seminar: 'sem',
        practical: 'pr',
      } satisfies Record<LessonType, string>
    )[type]
  }

  return (
    {
      lecture: 'л',
      laboratory: 'лаб',
      seminar: 'с',
      practical: 'пр',
    } satisfies Record<LessonType, string>
  )[type]
}

function weekParityLabel(entry: AdminScheduleEntry): string {
  return entry.weekParity === 'both' ? '2w' : entry.weekParity
}

function subgroupLabel(entry: AdminScheduleEntry): string | null {
  if (!entry.subgroup) {
    return null
  }

  return `${publicLabels.value.subgroup} ${entry.subgroup === 1 ? 'I' : 'II'}`
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
            v-for="day in scheduleWeekdays"
            :key="`${slot.id}-${day}`"
            :class="['schedule-grid__drop-cell', { 'schedule-grid__drop-cell--empty': entriesFor(day, slot.id).length === 0 }]"
            data-testid="schedule-cell"
            @dragover="!readOnly && $event.preventDefault()"
            @drop="drop($event, day, slot.id)"
          >
            <span v-if="entriesFor(day, slot.id).length === 0 && !readOnly" class="drop-cell-hint">
              {{ dropHint }}
            </span>
            <article
              v-for="entry in entriesFor(day, slot.id)"
              :key="entry.id"
              :class="['editor-entry', { 'editor-entry--conflict': hasConflict(entry) }]"
              data-testid="schedule-entry"
              :draggable="!readOnly"
              @dragstart="dragEntry($event, entry)"
            >
              <button
                type="button"
                class="editor-entry__select"
                data-testid="schedule-entry-select"
                @click="emit('select', entry)"
              >
                <strong>{{ entryTitleWithMarker(entry) }}</strong>
                <em>{{ groupNames(entry) }}<template v-if="subgroupLabel(entry)"> · {{ subgroupLabel(entry) }}</template></em>
                <span>{{ teacherName(entry.teacherId) }}</span>
                <small>{{ roomName(entry.roomId) }} · {{ weekParityLabel(entry) }}</small>
                <b v-if="hasConflict(entry)" class="editor-entry__conflict">
                  {{ conflictMessages?.[entry.id]?.[0] ?? conflictLabel }}
                </b>
              </button>
            </article>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
