<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import {
  createScheduleEntry,
  deleteScheduleEntry,
  getSchedule,
  listLessonCards,
  listRooms,
  listTimeSlots,
  updateScheduleEntry,
  validateSchedule,
} from '@/api/adminSchedule'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import LessonRequirementCard from '@/components/molecules/LessonRequirementCard.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import ScheduleEntryEditor from '@/components/organisms/ScheduleEntryEditor.vue'
import ScheduleEntryGrid from '@/components/organisms/ScheduleEntryGrid.vue'
import { adminCopy } from '@/i18n/admin'
import type {
  AdminRoom,
  AdminSchedule,
  AdminScheduleEntry,
  AdminTimeSlot,
  LessonCard,
  ScheduleEntryPayload,
  ScheduleValidationConflict,
} from '@/types/adminSchedule'

const route = useRoute()
const scheduleId = Number(route.params.id)
const schedule = ref<AdminSchedule | null>(null)
const cards = ref<LessonCard[]>([])
const rooms = ref<AdminRoom[]>([])
const timeSlots = ref<AdminTimeSlot[]>([])
const selectedRoomId = ref<number | null>(null)
const selectedEntry = ref<AdminScheduleEntry | null>(null)
const conflicts = ref<ScheduleValidationConflict[]>([])
const message = ref<string | null>(null)
const error = ref<string | null>(null)
const isLoading = ref(true)

const roomOptions = computed(() =>
  rooms.value.map((room) => ({ id: room.id, label: room.name, description: `${room.type}, ${room.capacity}` })),
)

onMounted(load)

async function load(): Promise<void> {
  isLoading.value = true
  error.value = null

  try {
    const [scheduleResponse, cardsResponse, roomResponse, slotResponse] = await Promise.all([
      getSchedule(scheduleId),
      listLessonCards(scheduleId),
      listRooms(),
      listTimeSlots(),
    ])
    schedule.value = scheduleResponse
    cards.value = cardsResponse.items
    rooms.value = roomResponse.items
    timeSlots.value = slotResponse.items
    selectedRoomId.value = roomResponse.items[0]?.id ?? null
  } catch {
    error.value = adminCopy.apiError
  } finally {
    isLoading.value = false
  }
}

async function place(payload: { card: LessonCard; dayOfWeek: number; timeSlotId: number }): Promise<void> {
  if (selectedRoomId.value === null) {
    error.value = adminCopy.selectRoom
    return
  }

  await createScheduleEntry(scheduleId, entryPayload(payload.card, payload.dayOfWeek, payload.timeSlotId))
  await load()
}

async function saveEntry(payload: Partial<ScheduleEntryPayload>): Promise<void> {
  if (selectedEntry.value === null) {
    return
  }

  await updateScheduleEntry(scheduleId, selectedEntry.value.id, payload)
  selectedEntry.value = null
  await load()
}

async function removeEntry(): Promise<void> {
  if (selectedEntry.value === null) {
    return
  }

  await deleteScheduleEntry(scheduleId, selectedEntry.value.id)
  selectedEntry.value = null
  await load()
}

async function validate(): Promise<void> {
  const result = await validateSchedule(scheduleId)
  conflicts.value = result.conflicts
  message.value = result.valid ? adminCopy.validationPassed : adminCopy.validationFailed
}

function entryPayload(card: LessonCard, dayOfWeek: number, timeSlotId: number): ScheduleEntryPayload {
  return {
    teachingLoadIds: [card.teachingLoadId],
    subjectId: card.subject.id,
    teacherId: card.teacher.id,
    lessonType: card.lessonType,
    roomId: selectedRoomId.value as number,
    timeSlotId,
    dayOfWeek,
    weekParity: 'both',
    groupIds: [card.group.id],
  }
}
</script>

<template>
  <AdminLayout>
    <StateMessage v-if="error" tone="error" :title="error" data-testid="editor-error" />
    <StateMessage v-else-if="isLoading" title="Завантаження..." />
    <section v-else-if="schedule" class="schedule-editor-page">
      <header class="schedule-editor-page__header">
        <div>
          <h1>{{ adminCopy.scheduleEditor }} #{{ schedule.id }}</h1>
          <p>{{ schedule.validFrom }} - {{ schedule.validTo }}</p>
        </div>
        <AppButton variant="primary" data-testid="validate-schedule" @click="validate">
          {{ adminCopy.validate }}
        </AppButton>
      </header>
      <StateMessage v-if="message" :title="message" data-testid="validation-result">
        <ul v-if="conflicts.length > 0">
          <li v-for="conflict in conflicts" :key="`${conflict.type}-${conflict.message}`">
            {{ conflict.message }}
          </li>
        </ul>
      </StateMessage>
      <div class="schedule-editor-layout">
        <aside class="lesson-card-panel">
          <h2>{{ adminCopy.lessonCards }}</h2>
          <AppSelect
            id="placement-room"
            :label="adminCopy.room"
            :model-value="selectedRoomId ?? ''"
            :options="roomOptions"
            @update:model-value="selectedRoomId = Number($event)"
          />
          <StateMessage v-if="cards.length === 0" :title="adminCopy.noCards" />
          <LessonRequirementCard v-for="card in cards" v-else :key="card.teachingLoadId" :card="card" />
        </aside>
        <ScheduleEntryGrid
          :entries="schedule.entries"
          :time-slots="timeSlots"
          @place="place"
          @select="selectedEntry = $event"
        />
        <ScheduleEntryEditor
          :entry="selectedEntry"
          :rooms="rooms"
          @save="saveEntry"
          @delete="removeEntry"
        />
      </div>
    </section>
  </AdminLayout>
</template>
