<script setup lang="ts">
import { computed } from 'vue'

import { adminCopy } from '@/i18n/admin'
import { labels } from '@/i18n/publicSchedule'
import type {
  AdminScheduleEntry,
  AdminTimeSlot,
  LessonCard,
  WeekParity,
} from '@/types/adminSchedule'

const props = defineProps<{
  entries: AdminScheduleEntry[]
  timeSlots: AdminTimeSlot[]
}>()

const emit = defineEmits<{
  place: [payload: { card: LessonCard; dayOfWeek: number; timeSlotId: number }]
  select: [entry: AdminScheduleEntry]
}>()

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
  const raw = event.dataTransfer?.getData('application/json')
  if (!raw) {
    return
  }

  emit('place', { card: JSON.parse(raw) as LessonCard, dayOfWeek, timeSlotId })
}

function weekParityLabel(value: WeekParity): string {
  return adminCopy.weekParityOptions[value]
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
          <th v-for="(day, index) in labels.days" :key="day" scope="col">
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
              class="editor-entry"
              data-testid="schedule-entry"
              @click="emit('select', entry)"
            >
              <strong>{{ labels.lessonTypes[entry.lessonType] ?? entry.lessonType }}</strong>
              <span>{{ weekParityLabel(entry.weekParity) }}</span>
              <small>{{ adminCopy.room }} #{{ entry.roomId }}</small>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
