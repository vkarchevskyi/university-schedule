<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { usePublicScheduleI18n } from '@/composables/useI18n'
import { addWeeks, currentWeekStart, formatDisplayDate, scheduleWeekDates } from '@/utils/date'

const props = defineProps<{
  weekStart: string
}>()

const emit = defineEmits<{
  'update:weekStart': [value: string]
}>()

const { t: labels } = usePublicScheduleI18n()

function move(weeks: number): void {
  emit('update:weekStart', addWeeks(props.weekStart, weeks))
}

function reset(): void {
  emit('update:weekStart', currentWeekStart())
}
</script>

<template>
  <div class="week-navigator" data-testid="week-navigator">
    <span class="week-navigator__label">
      {{ labels.week }}: {{ formatDisplayDate(scheduleWeekDates(weekStart)[0] ?? weekStart) }} -
      {{ formatDisplayDate(scheduleWeekDates(weekStart)[4] ?? weekStart) }}
    </span>
    <div class="week-navigator__actions">
      <AppButton variant="ghost" data-testid="previous-week" @click="move(-1)">
        {{ labels.previousWeek }}
      </AppButton>
      <AppButton variant="secondary" data-testid="current-week" @click="reset">
        {{ labels.currentWeek }}
      </AppButton>
      <AppButton variant="ghost" data-testid="next-week" @click="move(1)">
        {{ labels.nextWeek }}
      </AppButton>
    </div>
  </div>
</template>
