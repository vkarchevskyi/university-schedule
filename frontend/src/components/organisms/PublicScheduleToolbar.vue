<script setup lang="ts">
import EntityFilter from '@/components/molecules/EntityFilter.vue'
import WeekNavigator from '@/components/molecules/WeekNavigator.vue'
import type { LookupOption, PublicScheduleFilterType } from '@/types/publicSchedule'

defineProps<{
  type: PublicScheduleFilterType
  selectedId: number | null
  options: LookupOption[]
  weekStart: string
}>()

const emit = defineEmits<{
  'update:type': [value: PublicScheduleFilterType]
  'update:selectedId': [value: number]
  'update:weekStart': [value: string]
}>()
</script>

<template>
  <section class="schedule-toolbar" aria-label="Schedule filters">
    <EntityFilter
      :type="type"
      :selected-id="selectedId"
      :options="options"
      @update:type="emit('update:type', $event)"
      @update:selected-id="emit('update:selectedId', $event)"
    />
    <WeekNavigator :week-start="weekStart" @update:week-start="emit('update:weekStart', $event)" />
  </section>
</template>
