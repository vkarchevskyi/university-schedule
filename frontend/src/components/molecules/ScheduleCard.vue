<script setup lang="ts">
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import { usePublicScheduleI18n } from '@/composables/useI18n'
import type { ScheduleItem } from '@/types/publicSchedule'

defineProps<{
  item: ScheduleItem
}>()

const { t: labels } = usePublicScheduleI18n()

function teacherName(item: ScheduleItem): string {
  return `${item.teacher.firstName} ${item.teacher.lastName}`
}

function lessonTypeLabel(type: string): string {
  const lessonTypes: Record<string, string> = labels.value.lessonTypes

  return lessonTypes[type] ?? type
}

function subgroupLabel(subgroup: number): string {
  return `${labels.value.subgroup} ${subgroup === 1 ? 'I' : 'II'}`
}
</script>

<template>
  <article class="schedule-card" data-testid="schedule-card">
    <div class="schedule-card__header">
      <strong>{{ item.subject.name }}</strong>
      <StatusBadge tone="info">{{ lessonTypeLabel(item.lessonType) }}</StatusBadge>
      <StatusBadge v-if="item.subgroup" tone="secondary">{{ subgroupLabel(item.subgroup) }}</StatusBadge>
    </div>
    <dl class="schedule-card__details">
      <div>
        <dt>{{ labels.teacher }}</dt>
        <dd>{{ teacherName(item) }}</dd>
      </div>
      <div>
        <dt>{{ labels.room }}</dt>
        <dd>{{ item.room.name }}</dd>
      </div>
      <div>
        <dt>{{ labels.groups }}</dt>
        <dd>{{ item.groups.map((group) => group.name).join(', ') }}</dd>
      </div>
    </dl>
    <div class="schedule-card__badges">
      <StatusBadge v-if="item.isCancelled" tone="warning">{{ labels.cancelled }}</StatusBadge>
      <StatusBadge v-if="item.isOverride" tone="info">{{ labels.override }}</StatusBadge>
    </div>
  </article>
</template>
