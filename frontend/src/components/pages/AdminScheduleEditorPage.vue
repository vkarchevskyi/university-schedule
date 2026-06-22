<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import ConflictPanel from '@/components/molecules/ConflictPanel.vue'
import ErrorModal from '@/components/molecules/ErrorModal.vue'
import GenerationJobPanel from '@/components/molecules/GenerationJobPanel.vue'
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
  selectedGroupId,
  selectedTeacherId,
  selectedSubjectId,
  selectedLessonType,
  cardSort,
  cardsSearch,
  hideCompletedCards,
  requiresComputerRoomOnly,
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
  remainingLessonCount,
  scheduledLessonCount,
  requiredLessonCount,
  conflictCount,
  canPublish,
  publishReadinessLabel,
  pendingRoomOptions,
  groupOptions,
  teacherOptions,
  subjectOptions,
  filteredCards,
  filteredEntries,
  place,
  confirmPlacement,
  cancelPlacement,
  createEntry,
  moveEntry,
  saveEntry,
  removeEntry,
  duplicateEntry,
  validate,
  publish,
  clearActionError,
  generationJob,
  isGenerating,
  completeGeneration,
} = useAdminScheduleEditor(scheduleId)

const conflictEntryIds = computed(() => conflicts.value.flatMap((conflict) => conflict.entryIds))
const highlightedEntryIds = computed(() =>
  Array.from(new Set([...conflictEntryIds.value, ...errorEntryIds.value])),
)
const conflictMessagesByEntry = computed(() => {
  const messages: Record<number, string[]> = {}

  for (const conflict of conflicts.value) {
    for (const id of conflict.entryIds) {
      messages[id] = [...(messages[id] ?? []), conflict.message]
    }
  }

  return messages
})
const groupFilterOptions = computed(() => [
  { id: 0, label: t.value.allGroups, description: '' },
  ...groupOptions.value,
])
const teacherFilterOptions = computed(() => [
  { id: 0, label: t.value.allTeachers, description: '' },
  ...teacherOptions.value,
])
const subjectFilterOptions = computed(() => [
  { id: 0, label: t.value.allSubjects, description: '' },
  ...subjectOptions.value,
])

function selectGroup(value: string): void {
  const nextValue = Number(value)
  selectedGroupId.value = nextValue === 0 ? null : nextValue
  selectedEntry.value = null
}

function selectTeacher(value: string): void {
  const nextValue = Number(value)
  selectedTeacherId.value = nextValue === 0 ? null : nextValue
}

function selectSubject(value: string): void {
  const nextValue = Number(value)
  selectedSubjectId.value = nextValue === 0 ? null : nextValue
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
          <AppButton
            v-if="!isReadOnly && remainingLessonCount > 0"
            variant="secondary"
            data-testid="complete-generation"
            :disabled="isGenerating"
            @click="completeGeneration"
          >
            {{ t.completeGeneration }}
          </AppButton>
          <AppButton variant="primary" data-testid="validate-schedule" @click="validate">
            {{ t.validate }}
          </AppButton>
          <AppButton data-testid="publish-schedule" :disabled="!canPublish" @click="publish">
            {{ t.publish }}
          </AppButton>
        </div>
      </header>
      <GenerationJobPanel
        v-if="generationJob"
        :status="generationJob.status"
        :quality-score="generationJob.qualityScore"
        :quality-status="generationJob.qualityStatus"
        :diagnostics="generationJob.diagnostics"
        :error-message="generationJob.errorMessage"
        :generated-id="generationJob.generatedScheduleId"
        :open-label="t.openGeneratedSchedule"
        testid="editor-generation-job"
        @open="() => {}"
      />
      <section class="schedule-summary-bar" aria-label="Schedule summary">
        <article>
          <span>{{ t.status }}</span>
          <strong>{{ t.scheduleStatuses[schedule.status] ?? schedule.status }}</strong>
        </article>
        <article>
          <span>{{ t.remainingLessons }}</span>
          <strong>{{ remainingLessonCount }}</strong>
          <small>{{ scheduledLessonCount }} / {{ requiredLessonCount }}</small>
        </article>
        <article>
          <span>{{ t.conflictsCount }}</span>
          <strong>{{ conflictCount }}</strong>
        </article>
        <article>
          <span>{{ t.lastValidation }}</span>
          <strong>{{ publishReadinessLabel }}</strong>
        </article>
      </section>
      <StateMessage v-if="message" :title="message" data-testid="validation-result"> </StateMessage>
      <ConflictPanel :conflicts="conflicts" />
      <div class="schedule-editor-layout">
        <aside class="lesson-card-panel">
          <header class="panel-header">
            <div>
              <h2>{{ t.lessonCards }}</h2>
              <p>{{ t.remainingLessons }}: {{ remainingLessonCount }}</p>
            </div>
            <label class="inline-toggle">
              <input v-model="hideCompletedCards" type="checkbox" />
              <span>{{ t.hideCompleted }}</span>
            </label>
          </header>
          <div class="lesson-card-filters">
            <label class="field">
              <span class="field__label">{{ t.search }}</span>
              <input
                v-model="cardsSearch"
                class="field__control"
                type="search"
                :placeholder="t.searchPlaceholder"
              />
            </label>
            <AppSelect
              id="schedule-group-filter"
              data-testid="schedule-group-filter"
              :label="t.groups"
              :model-value="selectedGroupId ?? 0"
              :options="groupFilterOptions"
              @update:model-value="selectGroup"
            />
            <AppSelect
              id="schedule-teacher-filter"
              :label="t.teacher"
              :model-value="selectedTeacherId ?? 0"
              :options="teacherFilterOptions"
              @update:model-value="selectTeacher"
            />
            <AppSelect
              id="schedule-subject-filter"
              :label="t.subject"
              :model-value="selectedSubjectId ?? 0"
              :options="subjectFilterOptions"
              @update:model-value="selectSubject"
            />
            <label class="field">
              <span class="field__label">{{ t.lessonType }}</span>
              <select v-model="selectedLessonType" class="field__control">
                <option value="">{{ t.allLessonTypes }}</option>
                <option value="lecture">{{ t.lessonTypes.lecture }}</option>
                <option value="laboratory">{{ t.lessonTypes.laboratory }}</option>
                <option value="seminar">{{ t.lessonTypes.seminar }}</option>
                <option value="practical">{{ t.lessonTypes.practical }}</option>
              </select>
            </label>
            <label class="field">
              <span class="field__label">{{ t.orderBy }}</span>
              <select v-model="cardSort" class="field__control">
                <option value="remaining">{{ t.cardOrder.remaining }}</option>
                <option value="subject">{{ t.cardOrder.subject }}</option>
                <option value="group">{{ t.cardOrder.group }}</option>
                <option value="teacher">{{ t.cardOrder.teacher }}</option>
              </select>
            </label>
            <label class="inline-toggle">
              <input v-model="requiresComputerRoomOnly" type="checkbox" />
              <span>{{ t.computerRoomOnly }}</span>
            </label>
          </div>
          <StateMessage v-if="filteredCards.length === 0" :title="cards.length === 0 ? t.noCards : t.noFilteredCards" />
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
          :groups="groups"
          :subjects="subjects"
          :teachers="teachers"
          :time-slots="timeSlots"
          :conflict-entry-ids="highlightedEntryIds"
          :conflict-messages="conflictMessagesByEntry"
          :drop-hint="t.dropHere"
          :conflict-label="t.conflictReason"
          :read-only="isReadOnly"
          @place="place"
          @move="moveEntry($event.entry, $event.dayOfWeek, $event.timeSlotId)"
          @select="selectedEntry = $event"
        />
        <aside class="schedule-side-panel">
          <section v-if="pendingPlacement" class="placement-panel" data-testid="room-selection-modal">
            <header class="panel-header">
              <div>
                <h2>{{ t.placementDetails }}</h2>
                <p>{{ pendingPlacement.card.subject.name }} · {{ pendingPlacement.card.group.name }}</p>
              </div>
            </header>
            <AppSelect
              id="room-selection"
              data-testid="room-selection-room"
              :label="t.room"
              :model-value="pendingPlacement.selectedRoomId"
              :options="pendingRoomOptions"
              @update:model-value="pendingPlacement.selectedRoomId = Number($event)"
            />
            <footer class="entry-editor__actions">
              <AppButton data-testid="cancel-room-selection" @click="cancelPlacement">
                {{ t.cancel }}
              </AppButton>
              <AppButton variant="primary" data-testid="confirm-room-selection" @click="confirmPlacement">
                {{ t.add }}
              </AppButton>
            </footer>
          </section>
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
            @duplicate="duplicateEntry"
            @clear="selectedEntry = null"
          />
        </aside>
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
