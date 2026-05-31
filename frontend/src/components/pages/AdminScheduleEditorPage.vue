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
  rooms,
  groups,
  teachers,
  subjects,
  timeSlots,
  selectedGroupId,
  selectedEntry,
  pendingPlacement,
  conflicts,
  entryErrors,
  errorEntryIds,
  message,
  error,
  actionError,
  isLoading,
  isReadOnly,
  pendingRoomOptions,
  groupOptions,
  filteredCards,
  filteredEntries,
  place,
  confirmPlacement,
  cancelPlacement,
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

function selectGroup(value: string): void {
  selectedGroupId.value = Number(value)
  selectedEntry.value = null
}
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
        <div class="schedule-editor-page__controls">
          <AppSelect
            id="schedule-group-filter"
            data-testid="schedule-group-filter"
            :label="t.groups"
            :model-value="selectedGroupId ?? ''"
            :options="groupOptions"
            @update:model-value="selectGroup"
          />
          <AppButton variant="primary" data-testid="validate-schedule" @click="validate">
            {{ t.validate }}
          </AppButton>
          <AppButton data-testid="publish-schedule" @click="publish">
            {{ t.publish }}
          </AppButton>
        </div>
      </header>
      <StateMessage v-if="message" :title="message" data-testid="validation-result"> </StateMessage>
      <ConflictPanel :conflicts="conflicts" />
      <div class="schedule-editor-layout">
        <aside class="lesson-card-panel">
          <h2>{{ t.lessonCards }}</h2>
          <StateMessage v-if="filteredCards.length === 0" :title="t.noCards" />
          <LessonRequirementCard
            v-for="card in filteredCards"
            v-else
            :key="card.teachingLoadId"
            :card="card"
            :disabled="isReadOnly"
          />
        </aside>
        <ScheduleEntryGrid
          :entries="filteredEntries"
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
          :lesson-cards="filteredCards"
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
    <div v-if="pendingPlacement" class="modal-backdrop" data-testid="room-selection-modal">
      <section class="modal-panel room-selection-dialog" role="dialog" aria-modal="true">
        <header class="modal-panel__header">
          <h2>{{ t.chooseRoom }}</h2>
        </header>
        <p>
          {{ pendingPlacement.card.subject.name }} · {{ pendingPlacement.card.group.name }}
        </p>
        <AppSelect
          id="room-selection"
          data-testid="room-selection-room"
          :label="t.room"
          :model-value="pendingPlacement.selectedRoomId"
          :options="pendingRoomOptions"
          @update:model-value="pendingPlacement.selectedRoomId = Number($event)"
        />
        <footer class="modal-panel__footer">
          <AppButton data-testid="cancel-room-selection" @click="cancelPlacement">
            {{ t.cancel }}
          </AppButton>
          <AppButton
            variant="primary"
            data-testid="confirm-room-selection"
            @click="confirmPlacement"
          >
            {{ t.add }}
          </AppButton>
        </footer>
      </section>
    </div>
  </AdminLayout>
</template>
