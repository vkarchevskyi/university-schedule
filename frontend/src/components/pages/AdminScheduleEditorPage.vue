<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import ConflictPanel from '@/components/molecules/ConflictPanel.vue'
import ErrorModal from '@/components/molecules/ErrorModal.vue'
import LessonRequirementCard from '@/components/molecules/LessonRequirementCard.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import ScheduleEntryEditor from '@/components/organisms/ScheduleEntryEditor.vue'
import ScheduleEntryGrid from '@/components/organisms/ScheduleEntryGrid.vue'
import { useAdminScheduleEditor } from '@/composables/useAdminScheduleEditor'
import { useAdminI18n } from '@/composables/useI18n'

const route = useRoute()
const scheduleId = Number(route.params.id)
const { t } = useAdminI18n()
const {
  schedule,
  cards,
  rooms,
  groups,
  teachers,
  subjects,
  timeSlots,
  selectedRoomId,
  selectedEntry,
  conflicts,
  entryErrors,
  errorEntryIds,
  message,
  error,
  actionError,
  isLoading,
  isReadOnly,
  roomOptions,
  place,
  createEntry,
  moveEntry,
  saveEntry,
  removeEntry,
  validate,
  publish,
  clearActionError,
} = useAdminScheduleEditor(scheduleId)

const conflictEntryIds = computed(() => conflicts.value.flatMap((conflict) => conflict.entryIds))
const highlightedEntryIds = computed(() =>
  Array.from(new Set([...conflictEntryIds.value, ...errorEntryIds.value])),
)
</script>

<template>
  <AdminLayout>
    <StateMessage v-if="error" tone="error" :title="error" data-testid="editor-error" />
    <StateMessage v-else-if="isLoading" :title="t.loading" />
    <section v-else-if="schedule" class="schedule-editor-page">
      <header class="schedule-editor-page__header">
        <div>
          <h1>{{ t.scheduleEditor }} #{{ schedule.id }}</h1>
          <p>{{ schedule.validFrom }} - {{ schedule.validTo }}</p>
        </div>
        <AppButton variant="primary" data-testid="validate-schedule" @click="validate">
          {{ t.validate }}
        </AppButton>
        <AppButton data-testid="publish-schedule" @click="publish">
          {{ t.publish }}
        </AppButton>
      </header>
      <StateMessage v-if="message" :title="message" data-testid="validation-result"> </StateMessage>
      <ConflictPanel :conflicts="conflicts" />
      <div class="schedule-editor-layout">
        <aside class="lesson-card-panel">
          <h2>{{ t.lessonCards }}</h2>
          <AppSelect
            id="placement-room"
            :label="t.room"
            :model-value="selectedRoomId ?? ''"
            :options="roomOptions"
            @update:model-value="selectedRoomId = Number($event)"
          />
          <StateMessage v-if="cards.length === 0" :title="t.noCards" />
          <LessonRequirementCard
            v-for="card in cards"
            v-else
            :key="card.teachingLoadId"
            :card="card"
            :disabled="isReadOnly"
          />
        </aside>
        <ScheduleEntryGrid
          :entries="schedule.entries"
          :groups="groups"
          :rooms="rooms"
          :subjects="subjects"
          :teachers="teachers"
          :time-slots="timeSlots"
          :conflict-entry-ids="highlightedEntryIds"
          :read-only="isReadOnly"
          @place="place"
          @move="moveEntry($event.entry, $event.dayOfWeek, $event.timeSlotId)"
          @select="selectedEntry = $event"
        />
        <ScheduleEntryEditor
          :entry="selectedEntry"
          :groups="groups"
          :lesson-cards="cards"
          :rooms="rooms"
          :subjects="subjects"
          :teachers="teachers"
          :time-slots="timeSlots"
          :errors="entryErrors"
          @create="createEntry"
          @save="saveEntry"
          @delete="removeEntry"
        />
      </div>
    </section>
    <ErrorModal
      v-if="actionError.title"
      :title="actionError.title"
      :message="actionError.message"
      :details="actionError.details"
      @close="clearActionError"
    />
  </AdminLayout>
</template>
