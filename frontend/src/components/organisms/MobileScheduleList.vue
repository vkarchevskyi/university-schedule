<script setup lang="ts">
import ScheduleCard from '@/components/molecules/ScheduleCard.vue'
import { labels } from '@/i18n/publicSchedule'
import type { ScheduleItem } from '@/types/publicSchedule'
import { formatDisplayDate, weekDates } from '@/utils/date'

const props = defineProps<{
  weekStart: string
  items: ScheduleItem[]
}>()

function itemsFor(date: string): ScheduleItem[] {
  return props.items
    .filter((item) => item.date === date)
    .sort((left, right) => left.timeSlot.number - right.timeSlot.number)
}
</script>

<template>
  <div class="mobile-schedule" data-testid="mobile-schedule">
    <section v-for="(date, index) in weekDates(weekStart)" :key="date" class="mobile-schedule__day">
      <h2>
        {{ labels.days[index] }} <span>{{ formatDisplayDate(date) }}</span>
      </h2>
      <div v-if="itemsFor(date).length > 0" class="mobile-schedule__items">
        <div v-for="item in itemsFor(date)" :key="item.id" class="mobile-schedule__item">
          <time
            >{{ item.timeSlot.startsAt.slice(0, 5) }}-{{ item.timeSlot.endsAt.slice(0, 5) }}</time
          >
          <ScheduleCard :item="item" />
        </div>
      </div>
    </section>
  </div>
</template>
