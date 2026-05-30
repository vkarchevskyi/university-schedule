<script setup lang="ts">
import { useAdminI18n, usePublicScheduleI18n } from '@/composables/useI18n'
import type { LessonCard } from '@/types/adminSchedule'

defineProps<{
  card: LessonCard
  disabled?: boolean
}>()

const { t } = useAdminI18n()
const { t: labels } = usePublicScheduleI18n()

function teacherName(card: LessonCard): string {
  return `${card.teacher.firstName} ${card.teacher.lastName}`
}
</script>

<template>
  <article
    :class="[
      'lesson-requirement-card',
      { 'lesson-requirement-card--done': card.remainingLessonCount <= 0 || disabled },
    ]"
    :draggable="card.remainingLessonCount > 0 && !disabled"
    :aria-disabled="disabled"
    data-testid="lesson-card"
    @dragstart="
      card.remainingLessonCount > 0 &&
      !disabled &&
      $event.dataTransfer?.setData('application/json', JSON.stringify(card))
    "
  >
    <strong>{{ card.subject.name }}</strong>
    <span>{{ labels.lessonTypes[card.lessonType] ?? card.lessonType }}</span>
    <small>{{ card.group.name }} · {{ teacherName(card) }}</small>
    <dl>
      <div>
        <dt>{{ t.required }}</dt>
        <dd>{{ card.requiredLessonCount }}</dd>
      </div>
      <div>
        <dt>{{ t.scheduled }}</dt>
        <dd>{{ card.scheduledLessonCount }}</dd>
      </div>
      <div>
        <dt>{{ t.remaining }}</dt>
        <dd>{{ card.remainingLessonCount }}</dd>
      </div>
    </dl>
  </article>
</template>
