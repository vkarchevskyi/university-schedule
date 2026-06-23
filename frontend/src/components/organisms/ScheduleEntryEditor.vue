<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import CheckboxGroupField from '@/components/molecules/CheckboxGroupField.vue'
import ConfirmActionButton from '@/components/molecules/ConfirmActionButton.vue'
import { useAdminI18n, usePublicScheduleI18n } from '@/composables/useI18n'
import { scheduleWeekdays } from '@/utils/date'
import type {
  AdminGroup,
  AdminRoom,
  AdminScheduleEntry,
  AdminSubject,
  AdminTeacher,
  AdminTimeSlot,
  LessonCard,
  LookupOption,
  LessonType,
  ScheduleEntryPayload,
  WeekParity,
} from '@/types/adminSchedule'

const props = defineProps<{
  entry: AdminScheduleEntry | null
  groups: AdminGroup[]
  lessonCards: LessonCard[]
  rooms: AdminRoom[]
  subjects: AdminSubject[]
  teachers: AdminTeacher[]
  timeSlots: AdminTimeSlot[]
  errors: Record<string, string>
  readOnly?: boolean
}>()

const emit = defineEmits<{
  create: [payload: ScheduleEntryPayload]
  save: [payload: Partial<ScheduleEntryPayload>]
  delete: []
  duplicate: [entry: AdminScheduleEntry]
  clear: []
}>()

const { t } = useAdminI18n()
const { t: publicLabels } = usePublicScheduleI18n()

const roomId = ref<number | null>(null)
const subjectId = ref<number | null>(null)
const teacherId = ref<number | null>(null)
const timeSlotId = ref<number | null>(null)
const dayOfWeek = ref<number>(1)
const lessonType = ref<LessonType>('lecture')
const weekParity = ref<WeekParity>('both')
const groupIds = ref<number[]>([])
const teachingLoadIds = ref<number[]>([])
const teachingLoadId = ref<number | null>(null)
const subgroup = ref<number | null>(null)
const isSyncingGroups = ref(false)

const roomOptions = computed<LookupOption[]>(() =>
  props.rooms.map((room) => ({
    id: room.id,
    label: room.name,
    description: `${room.type}, ${room.capacity}`,
  })),
)

const subjectOptions = computed<LookupOption[]>(() =>
  props.subjects.map((subject) => ({ id: subject.id, label: subject.name, description: '' })),
)

const teacherOptions = computed<LookupOption[]>(() =>
  props.teachers.map((teacher) => ({
    id: teacher.id,
    label: `${teacher.firstName} ${teacher.lastName}`,
    description: '',
  })),
)

function matchesLessonContext(card: LessonCard): boolean {
  return (
    subjectId.value !== null &&
    teacherId.value !== null &&
    card.subject.id === subjectId.value &&
    card.teacher.id === teacherId.value &&
    card.lessonType === lessonType.value &&
    (card.subgroup ?? null) === (subgroup.value ?? null)
  )
}

const groupCheckboxOptions = computed(() => {
  const hasLessonContext = subjectId.value !== null && teacherId.value !== null

  return props.groups
    .filter((group) => {
      if (!hasLessonContext) {
        return true
      }

      const hasMatchingCard = props.lessonCards.some(
        (card) => card.group.id === group.id && matchesLessonContext(card),
      )

      return hasMatchingCard || groupIds.value.includes(group.id)
    })
    .map((group) => ({ id: group.id, label: group.name }))
})

const linkedGroupNames = computed(() =>
  props.lessonCards
    .filter((card) => teachingLoadIds.value.includes(card.teachingLoadId))
    .map((card) => card.group.name)
    .join(', '),
)

const timeSlotOptions = computed<LookupOption[]>(() =>
  props.timeSlots.map((slot) => ({
    id: slot.id,
    label: `${slot.number}. ${slot.startsAt}-${slot.endsAt}`,
    description: '',
  })),
)

const teachingLoadOptions = computed<LookupOption[]>(() =>
  props.lessonCards.map((card) => ({
    id: card.teachingLoadId,
    label: `${card.subject.name} · ${card.group.name}`,
    description: `${card.teacher.firstName} ${card.teacher.lastName}`,
  })),
)

const dayOptions = computed(() =>
  scheduleWeekdays.map((day) => ({
    id: day,
    label: publicLabels.value.days[day - 1] ?? String(day),
    description: '',
  })),
)

const lessonTypeOptions = computed(() => [
  { id: 'lecture', label: publicLabels.value.lessonTypes.lecture, description: '' },
  { id: 'laboratory', label: publicLabels.value.lessonTypes.laboratory, description: '' },
  { id: 'seminar', label: publicLabels.value.lessonTypes.seminar, description: '' },
  { id: 'practical', label: publicLabels.value.lessonTypes.practical, description: '' },
])

const weekParityOptions = computed(() => [
  { id: 'both', label: t.value.weekParityOptions.both, description: '' },
  { id: 'odd', label: t.value.weekParityOptions.odd, description: '' },
  { id: 'even', label: t.value.weekParityOptions.even, description: '' },
])

const subgroupOptions = computed(() => [
  { id: 0, label: t.value.subgroupOptions.none, description: '' },
  { id: 1, label: t.value.subgroupOptions.one, description: '' },
  { id: 2, label: t.value.subgroupOptions.two, description: '' },
])

const errorMessages = computed(() => Object.values(props.errors))

function syncTeachingLoadsFromGroups(): void {
  if (subjectId.value === null || teacherId.value === null) {
    return
  }

  teachingLoadIds.value = props.lessonCards
    .filter((card) => groupIds.value.includes(card.group.id) && matchesLessonContext(card))
    .map((card) => card.teachingLoadId)
}

function pruneGroupsToLessonCards(): void {
  if (subjectId.value === null || teacherId.value === null) {
    return
  }

  const validGroupIds = new Set(
    props.lessonCards.filter((card) => matchesLessonContext(card)).map((card) => card.group.id),
  )
  groupIds.value = groupIds.value.filter((id) => validGroupIds.has(id))
  syncTeachingLoadsFromGroups()
}

watch(
  () => props.entry,
  (entry) => {
    roomId.value = entry?.roomId ?? null
    subjectId.value = entry?.subjectId ?? null
    teacherId.value = entry?.teacherId ?? null
    timeSlotId.value = entry?.timeSlotId ?? null
    dayOfWeek.value = entry?.dayOfWeek ?? 1
    lessonType.value = entry?.lessonType ?? 'lecture'
    weekParity.value = entry?.weekParity ?? 'both'
    groupIds.value = entry?.groupIds ? [...entry.groupIds] : []
    teachingLoadIds.value = entry?.teachingLoadIds ? [...entry.teachingLoadIds] : []
    teachingLoadId.value =
      entry?.teachingLoadIds[0] ?? props.lessonCards[0]?.teachingLoadId ?? null
    subgroup.value = entry?.subgroup ?? null
  },
  { immediate: true },
)

watch(groupIds, () => {
  if (isSyncingGroups.value) {
    return
  }

  syncTeachingLoadsFromGroups()
}, { deep: true })

watch([subjectId, teacherId, lessonType, subgroup], () => {
  pruneGroupsToLessonCards()
})

watch(teachingLoadId, (id) => {
  if (props.entry !== null || id === null) {
    return
  }

  const card = props.lessonCards.find((item) => item.teachingLoadId === id)
  if (card === undefined) {
    return
  }

  subjectId.value = card.subject.id
  teacherId.value = card.teacher.id
  lessonType.value = card.lessonType
  subgroup.value = card.subgroup
  isSyncingGroups.value = true
  groupIds.value = [card.group.id]
  teachingLoadIds.value = [card.teachingLoadId]
  isSyncingGroups.value = false
})

function save(): void {
  if (
    roomId.value === null ||
    subjectId.value === null ||
    teacherId.value === null ||
    timeSlotId.value === null ||
    groupIds.value.length === 0 ||
    teachingLoadIds.value.length === 0
  ) {
    return
  }

  const payload: ScheduleEntryPayload = {
    teachingLoadIds: [...teachingLoadIds.value],
    subjectId: subjectId.value,
    teacherId: teacherId.value,
    lessonType: lessonType.value,
    roomId: roomId.value,
    timeSlotId: timeSlotId.value,
    dayOfWeek: dayOfWeek.value,
    weekParity: weekParity.value,
    groupIds: [...groupIds.value],
    subgroup: subgroup.value,
  }

  if (props.entry === null) {
    emit('create', payload)
    return
  }

  emit('save', payload)
}

function fieldError(field: string): string | undefined {
  return props.errors[field]
}
</script>

<template>
  <aside class="entry-editor" data-testid="entry-editor">
    <header class="entry-editor__header">
      <h2>{{ entry ? t.scheduleEditor : t.add }}</h2>
      <AppButton v-if="entry" variant="ghost" @click="emit('clear')">{{ t.clearSelection }}</AppButton>
    </header>
    <StateMessage
      v-if="readOnly"
      :title="t.readOnlySchedule"
      data-testid="entry-read-only-notice"
    />
    <StateMessage
      v-if="errorMessages.length > 0"
      tone="error"
      :title="t.validationFailed"
      data-testid="entry-validation-summary"
    >
      <ul>
        <li v-for="message in errorMessages" :key="message">{{ message }}</li>
      </ul>
    </StateMessage>
    <AppSelect
      id="entry-teaching-load"
      :label="t.lessonCards"
      :model-value="teachingLoadId ?? ''"
      :options="teachingLoadOptions"
      :error="fieldError('teachingLoadIds')"
      :disabled="readOnly"
      @update:model-value="teachingLoadId = Number($event)"
    />
    <AppSelect
      id="entry-subject"
      :label="t.subject"
      :model-value="subjectId ?? ''"
      :options="subjectOptions"
      :error="fieldError('subjectId')"
      :disabled="readOnly"
      @update:model-value="subjectId = Number($event)"
    />
    <AppSelect
      id="entry-teacher"
      :label="t.teacher"
      :model-value="teacherId ?? ''"
      :options="teacherOptions"
      :error="fieldError('teacherId')"
      :disabled="readOnly"
      @update:model-value="teacherId = Number($event)"
    />
    <AppSelect
      id="entry-lesson-type"
      :label="t.lessonType"
      :model-value="lessonType"
      :options="lessonTypeOptions"
      :error="fieldError('lessonType')"
      :disabled="readOnly"
      @update:model-value="lessonType = $event as LessonType"
    />
    <CheckboxGroupField
      v-model="groupIds"
      data-testid="entry-groups"
      :label="t.groups"
      :options="groupCheckboxOptions"
      :error="fieldError('groupIds')"
      :disabled="readOnly"
    />
    <small v-if="linkedGroupNames">{{ t.sharedClassGroups }}: {{ linkedGroupNames }}</small>
    <AppSelect
      id="entry-room"
      :label="t.room"
      :model-value="roomId ?? ''"
      :options="roomOptions"
      :error="fieldError('roomId')"
      :disabled="readOnly"
      @update:model-value="roomId = Number($event)"
    />
    <AppSelect
      id="entry-time-slot"
      :label="t.nav.timeSlots"
      :model-value="timeSlotId ?? ''"
      :options="timeSlotOptions"
      :error="fieldError('timeSlotId')"
      :disabled="readOnly"
      @update:model-value="timeSlotId = Number($event)"
    />
    <AppSelect
      id="entry-day"
      :label="t.day"
      :model-value="dayOfWeek"
      :options="dayOptions"
      :error="fieldError('dayOfWeek')"
      :disabled="readOnly"
      @update:model-value="dayOfWeek = Number($event)"
    />
    <AppSelect
      id="entry-lesson-type"
      :label="t.lessonType"
      :model-value="lessonType"
      :options="lessonTypeOptions"
      :error="fieldError('lessonType')"
      :disabled="readOnly"
      @update:model-value="lessonType = $event as LessonType"
    />
    <AppSelect
      id="entry-week-parity"
      data-testid="week-parity-select"
      :label="t.weekParity"
      :model-value="weekParity"
      :options="weekParityOptions"
      :error="fieldError('weekParity')"
      :disabled="readOnly"
      @update:model-value="weekParity = $event as WeekParity"
    />
    <AppSelect
      id="entry-subgroup"
      data-testid="subgroup-select"
      :label="t.subgroup"
      :model-value="subgroup ?? 0"
      :options="subgroupOptions"
      :error="fieldError('subgroup')"
      :disabled="readOnly"
      @update:model-value="subgroup = $event === '0' ? null : Number($event)"
    />
    <div v-if="!readOnly" class="entry-editor__actions">
      <AppButton variant="primary" data-testid="save-entry" @click="save">{{
        entry ? t.saveEntry : t.add
      }}</AppButton>
      <ConfirmActionButton
        v-if="entry"
        :message="t.deleteConfirm"
        testid="delete-entry"
        @confirm="emit('delete')"
      >
        {{ t.deleteEntry }}
      </ConfirmActionButton>
      <AppButton v-if="entry" @click="emit('duplicate', entry)">
        {{ t.duplicateEntry }}
      </AppButton>
    </div>
  </aside>
</template>
