<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import { adminCopy } from '@/i18n/admin'
import type { AdminRoom, AdminScheduleEntry, LookupOption, ScheduleEntryPayload, WeekParity } from '@/types/adminSchedule'

const props = defineProps<{
  entry: AdminScheduleEntry | null
  rooms: AdminRoom[]
}>()

const emit = defineEmits<{
  save: [payload: Partial<ScheduleEntryPayload>]
  delete: []
}>()

const roomId = ref<number | null>(null)
const weekParity = ref<WeekParity>('both')

const roomOptions = computed<LookupOption[]>(() =>
  props.rooms.map((room) => ({ id: room.id, label: room.name, description: `${room.type}, ${room.capacity}` })),
)

watch(
  () => props.entry,
  (entry) => {
    roomId.value = entry?.roomId ?? null
    weekParity.value = entry?.weekParity ?? 'both'
  },
  { immediate: true },
)

function save(): void {
  if (roomId.value === null) {
    return
  }

  emit('save', { roomId: roomId.value, weekParity: weekParity.value })
}
</script>

<template>
  <aside v-if="entry" class="entry-editor" data-testid="entry-editor">
    <h2>{{ adminCopy.scheduleEditor }}</h2>
    <AppSelect
      id="entry-room"
      :label="adminCopy.room"
      :model-value="roomId ?? ''"
      :options="roomOptions"
      @update:model-value="roomId = Number($event)"
    />
    <label class="field" for="entry-week-parity">
      <span class="field__label">{{ adminCopy.weekParity }}</span>
      <select id="entry-week-parity" v-model="weekParity" class="field__control" data-testid="week-parity-select">
        <option value="both">Обидва тижні</option>
        <option value="odd">Непарний</option>
        <option value="even">Парний</option>
      </select>
    </label>
    <div class="entry-editor__actions">
      <AppButton variant="primary" data-testid="save-entry" @click="save">{{ adminCopy.saveEntry }}</AppButton>
      <AppButton variant="ghost" data-testid="delete-entry" @click="emit('delete')">{{ adminCopy.deleteEntry }}</AppButton>
    </div>
  </aside>
</template>
