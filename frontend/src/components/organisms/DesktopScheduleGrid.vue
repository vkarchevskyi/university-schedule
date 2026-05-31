<script setup lang="ts">
import { computed } from 'vue'

import ScheduleCard from '@/components/molecules/ScheduleCard.vue'
import { usePublicScheduleI18n } from '@/composables/useI18n'
import type { ScheduleItem } from '@/types/publicSchedule'
import { formatDisplayDate, scheduleWeekDates } from '@/utils/date'

const props = defineProps<{
  weekStart: string
  items: ScheduleItem[]
}>()

const { t: labels } = usePublicScheduleI18n()

const slotNumbers = computed(() => [...new Set(props.items.map((item) => item.timeSlot.number))])
const itemsByCell = computed(() => {
  const map = new Map<string, ScheduleItem[]>()

  for (const item of props.items) {
    const key = cellKey(item.date, item.timeSlot.number)
    const items = map.get(key) ?? []
    items.push(item)
    map.set(key, items)
  }

  return map
})

function itemsFor(date: string, slotNumber: number): ScheduleItem[] {
  return itemsByCell.value.get(cellKey(date, slotNumber)) ?? []
}

function slotLabel(slotNumber: number): string {
  const item = props.items.find((scheduleItem) => scheduleItem.timeSlot.number === slotNumber)

  if (!item) {
    return String(slotNumber)
  }

  return `${item.timeSlot.startsAt.slice(0, 5)}-${item.timeSlot.endsAt.slice(0, 5)}`
}

function cellKey(date: string, slotNumber: number): string {
  return `${date}-${slotNumber}`
}
</script>

<template>
  <div class="desktop-schedule" data-testid="desktop-schedule">
    <table class="schedule-grid">
      <thead>
        <tr>
          <th class="schedule-grid__time-column" scope="col"></th>
          <th v-for="(date, index) in scheduleWeekDates(weekStart)" :key="date" scope="col">
            <span>{{ labels.days[index] }}</span>
            <small>{{ formatDisplayDate(date) }}</small>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="slotNumber in slotNumbers" :key="slotNumber">
          <th scope="row">{{ slotLabel(slotNumber) }}</th>
          <td v-for="date in scheduleWeekDates(weekStart)" :key="`${date}-${slotNumber}`">
            <ScheduleCard v-for="item in itemsFor(date, slotNumber)" :key="item.id" :item="item" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
