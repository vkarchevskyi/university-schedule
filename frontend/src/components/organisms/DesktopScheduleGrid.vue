<script setup lang="ts">
import ScheduleCard from '@/components/molecules/ScheduleCard.vue'
import { labels } from '@/i18n/publicSchedule'
import type { ScheduleItem } from '@/types/publicSchedule'
import { formatDisplayDate, weekDates } from '@/utils/date'

const props = defineProps<{
  weekStart: string
  items: ScheduleItem[]
}>()

function itemsFor(date: string, slotNumber: number): ScheduleItem[] {
  return props.items.filter((item) => item.date === date && item.timeSlot.number === slotNumber)
}

function slotLabel(slotNumber: number): string {
  const item = props.items.find((scheduleItem) => scheduleItem.timeSlot.number === slotNumber)

  if (!item) {
    return String(slotNumber)
  }

  return `${item.timeSlot.startsAt.slice(0, 5)}-${item.timeSlot.endsAt.slice(0, 5)}`
}
</script>

<template>
  <div class="desktop-schedule" data-testid="desktop-schedule">
    <table class="schedule-grid">
      <thead>
        <tr>
          <th class="schedule-grid__time-column" scope="col"></th>
          <th v-for="(date, index) in weekDates(weekStart)" :key="date" scope="col">
            <span>{{ labels.days[index] }}</span>
            <small>{{ formatDisplayDate(date) }}</small>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="slotNumber in [...new Set(items.map((item) => item.timeSlot.number))]" :key="slotNumber">
          <th scope="row">{{ slotLabel(slotNumber) }}</th>
          <td v-for="date in weekDates(weekStart)" :key="`${date}-${slotNumber}`">
            <ScheduleCard
              v-for="item in itemsFor(date, slotNumber)"
              :key="item.id"
              :item="item"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
