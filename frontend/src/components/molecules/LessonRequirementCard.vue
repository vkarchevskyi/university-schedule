<script setup lang="ts">
import { adminCopy } from '@/i18n/admin'
import { labels } from '@/i18n/publicSchedule'
import type { LessonCard } from '@/types/adminSchedule'

defineProps<{
  card: LessonCard
}>()

function teacherName(card: LessonCard): string {
  return `${card.teacher.firstName} ${card.teacher.lastName}`
}
</script>

<template>
  <article
    class="lesson-requirement-card"
    draggable="true"
    data-testid="lesson-card"
    @dragstart="$event.dataTransfer?.setData('application/json', JSON.stringify(card))"
  >
    <strong>{{ card.subject.name }}</strong>
    <span>{{ labels.lessonTypes[card.lessonType] ?? card.lessonType }}</span>
    <small>{{ card.group.name }} · {{ teacherName(card) }}</small>
    <dl>
      <div>
        <dt>{{ adminCopy.required }}</dt>
        <dd>{{ card.requiredLessonCount }}</dd>
      </div>
      <div>
        <dt>{{ adminCopy.scheduled }}</dt>
        <dd>{{ card.scheduledLessonCount }}</dd>
      </div>
      <div>
        <dt>{{ adminCopy.remaining }}</dt>
        <dd>{{ card.remainingLessonCount }}</dd>
      </div>
    </dl>
  </article>
</template>
