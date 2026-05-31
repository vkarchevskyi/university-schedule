<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
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
}>()

const emit = defineEmits<{
  create: [payload: ScheduleEntryPayload]
  save: [payload: Partial<ScheduleEntryPayload>]
  delete: []
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
const groupId = ref<number | null>(null)
const teachingLoadId = ref<number | null>(null)

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
    description: teacher.department,
  })),
)

const groupOptions = computed<LookupOption[]>(() =>
  props.groups.map((group) => ({
    id: group.id,
    label: group.name,
    description: `${group.speciality}, ${group.course}`,
  })),
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

const errorMessages = computed(() => Object.values(props.errors))

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
    groupId.value = entry?.groupIds[0] ?? null
    teachingLoadId.value = entry?.teachingLoadIds[0] ?? props.lessonCards[0]?.teachingLoadId ?? null
  },
  { immediate: true },
)

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
  groupId.value = card.group.id
  lessonType.value = card.lessonType
})

function save(): void {
  if (
    roomId.value === null ||
    subjectId.value === null ||
    teacherId.value === null ||
    timeSlotId.value === null ||
    groupId.value === null ||
    teachingLoadId.value === null
  ) {
    return
  }

  const payload: ScheduleEntryPayload = {
    teachingLoadIds: [teachingLoadId.value],
    subjectId: subjectId.value,
    teacherId: teacherId.value,
    lessonType: lessonType.value,
    roomId: roomId.value,
    timeSlotId: timeSlotId.value,
    dayOfWeek: dayOfWeek.value,
    weekParity: weekParity.value,
    groupIds: [groupId.value],
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
    <h2>{{ entry ? t.scheduleEditor : t.add }}</h2>
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
      @update:model-value="teachingLoadId = Number($event)"
    />
    <AppSelect
      id="entry-subject"
      :label="t.subject"
      :model-value="subjectId ?? ''"
      :options="subjectOptions"
      :error="fieldError('subjectId')"
      @update:model-value="subjectId = Number($event)"
    />
    <AppSelect
      id="entry-teacher"
      :label="t.teacher"
      :model-value="teacherId ?? ''"
      :options="teacherOptions"
      :error="fieldError('teacherId')"
      @update:model-value="teacherId = Number($event)"
    />
    <AppSelect
      id="entry-group"
      :label="t.group"
      :model-value="groupId ?? ''"
      :options="groupOptions"
      :error="fieldError('groupIds')"
      @update:model-value="groupId = Number($event)"
    />
    <AppSelect
      id="entry-room"
      :label="t.room"
      :model-value="roomId ?? ''"
      :options="roomOptions"
      :error="fieldError('roomId')"
      @update:model-value="roomId = Number($event)"
    />
    <AppSelect
      id="entry-time-slot"
      :label="t.nav.timeSlots"
      :model-value="timeSlotId ?? ''"
      :options="timeSlotOptions"
      :error="fieldError('timeSlotId')"
      @update:model-value="timeSlotId = Number($event)"
    />
    <label :class="['field', { 'field--invalid': fieldError('dayOfWeek') }]" for="entry-day">
      <span class="field__label">{{ t.day }}</span>
      <select id="entry-day" v-model.number="dayOfWeek" class="field__control">
        <option v-for="day in scheduleWeekdays" :key="day" :value="day">
          {{ publicLabels.days[day - 1] ?? day }}
        </option>
      </select>
      <small v-if="fieldError('dayOfWeek')" class="field-error">
        {{ fieldError('dayOfWeek') }}
      </small>
    </label>
    <label :class="['field', { 'field--invalid': fieldError('lessonType') }]" for="entry-lesson-type">
      <span class="field__label">{{ t.lessonType }}</span>
      <select id="entry-lesson-type" v-model="lessonType" class="field__control">
        <option value="lecture">{{ publicLabels.lessonTypes.lecture }}</option>
        <option value="laboratory">{{ publicLabels.lessonTypes.laboratory }}</option>
        <option value="seminar">{{ publicLabels.lessonTypes.seminar }}</option>
        <option value="practical">{{ publicLabels.lessonTypes.practical }}</option>
      </select>
      <small v-if="fieldError('lessonType')" class="field-error">
        {{ fieldError('lessonType') }}
      </small>
    </label>
    <label :class="['field', { 'field--invalid': fieldError('weekParity') }]" for="entry-week-parity">
      <span class="field__label">{{ t.weekParity }}</span>
      <select
        id="entry-week-parity"
        v-model="weekParity"
        class="field__control"
        data-testid="week-parity-select"
      >
        <option value="both">{{ t.weekParityOptions.both }}</option>
        <option value="odd">{{ t.weekParityOptions.odd }}</option>
        <option value="even">{{ t.weekParityOptions.even }}</option>
      </select>
      <small v-if="fieldError('weekParity')" class="field-error">
        {{ fieldError('weekParity') }}
      </small>
    </label>
    <div class="entry-editor__actions">
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
    </div>
  </aside>
</template>
