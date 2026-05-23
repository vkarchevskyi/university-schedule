<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import { adminCopy } from '@/i18n/admin'
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
}>()

const emit = defineEmits<{
  create: [payload: ScheduleEntryPayload]
  save: [payload: Partial<ScheduleEntryPayload>]
  delete: []
}>()

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
</script>

<template>
  <aside class="entry-editor" data-testid="entry-editor">
    <h2>{{ entry ? adminCopy.scheduleEditor : 'Нове заняття' }}</h2>
    <AppSelect
      id="entry-teaching-load"
      label="Картка заняття"
      :model-value="teachingLoadId ?? ''"
      :options="teachingLoadOptions"
      @update:model-value="teachingLoadId = Number($event)"
    />
    <AppSelect
      id="entry-subject"
      label="Предмет"
      :model-value="subjectId ?? ''"
      :options="subjectOptions"
      @update:model-value="subjectId = Number($event)"
    />
    <AppSelect
      id="entry-teacher"
      label="Викладач"
      :model-value="teacherId ?? ''"
      :options="teacherOptions"
      @update:model-value="teacherId = Number($event)"
    />
    <AppSelect
      id="entry-group"
      label="Група"
      :model-value="groupId ?? ''"
      :options="groupOptions"
      @update:model-value="groupId = Number($event)"
    />
    <AppSelect
      id="entry-room"
      :label="adminCopy.room"
      :model-value="roomId ?? ''"
      :options="roomOptions"
      @update:model-value="roomId = Number($event)"
    />
    <AppSelect
      id="entry-time-slot"
      label="Пара"
      :model-value="timeSlotId ?? ''"
      :options="timeSlotOptions"
      @update:model-value="timeSlotId = Number($event)"
    />
    <label class="field" for="entry-day">
      <span class="field__label">День</span>
      <select id="entry-day" v-model.number="dayOfWeek" class="field__control">
        <option v-for="day in 7" :key="day" :value="day">{{ day }}</option>
      </select>
    </label>
    <label class="field" for="entry-lesson-type">
      <span class="field__label">Тип заняття</span>
      <select id="entry-lesson-type" v-model="lessonType" class="field__control">
        <option value="lecture">Лекція</option>
        <option value="laboratory">Лабораторна</option>
        <option value="seminar">Семінар</option>
        <option value="practical">Практична</option>
      </select>
    </label>
    <label class="field" for="entry-week-parity">
      <span class="field__label">{{ adminCopy.weekParity }}</span>
      <select
        id="entry-week-parity"
        v-model="weekParity"
        class="field__control"
        data-testid="week-parity-select"
      >
        <option value="both">{{ adminCopy.weekParityOptions.both }}</option>
        <option value="odd">{{ adminCopy.weekParityOptions.odd }}</option>
        <option value="even">{{ adminCopy.weekParityOptions.even }}</option>
      </select>
    </label>
    <div class="entry-editor__actions">
      <AppButton variant="primary" data-testid="save-entry" @click="save">{{
        entry ? adminCopy.saveEntry : 'Додати'
      }}</AppButton>
      <AppButton v-if="entry" variant="ghost" data-testid="delete-entry" @click="emit('delete')">{{
        adminCopy.deleteEntry
      }}</AppButton>
    </div>
  </aside>
</template>
