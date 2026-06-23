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
import { translateScheduleConflict } from '@/utils/scheduleConflicts'

const route = useRoute()
const scheduleId = Number(route.params.id)
const { t, locale } = useAdminI18n()
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
  updateValidFrom,
  duplicateToDraft,
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
    const text = translateScheduleConflict(conflict.type, conflict.message, locale.locale)
    for (const id of conflict.entryIds) {
      messages[id] = [...(messages[id] ?? []), text]
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
const lessonTypeFilterOptions = computed(() => [
  { id: 'all', label: t.value.allLessonTypes, description: '' },
  { id: 'lecture', label: t.value.lessonTypes.lecture, description: '' },
  { id: 'laboratory', label: t.value.lessonTypes.laboratory, description: '' },
  { id: 'seminar', label: t.value.lessonTypes.seminar, description: '' },
  { id: 'practical', label: t.value.lessonTypes.practical, description: '' },
])
const cardSortOptions = computed(() => [
  { id: 'remaining', label: t.value.cardOrder.remaining, description: '' },
  { id: 'subject', label: t.value.cardOrder.subject, description: '' },
  { id: 'group', label: t.value.cardOrder.group, description: '' },
  { id: 'teacher', label: t.value.cardOrder.teacher, description: '' },
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

function selectLessonType(value: string): void {
  selectedLessonType.value = value
}

function selectCardSort(value: string): void {
  cardSort.value = value as 'remaining' | 'subject' | 'group' | 'teacher'
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
          <p v-if="isReadOnly">{{ schedule.validFrom }} - {{ schedule.validTo }}</p>
          <div v-else class="field schedule-editor-page__valid-from">
            <label class="field__label" for="schedule-valid-from">{{ t.validFrom }}</label>
            <input
              id="schedule-valid-from"
              class="field__control"
              type="date"
              data-testid="schedule-valid-from"
              :value="schedule.validFrom"
              @change="updateValidFrom(($event.target as HTMLInputElement).value)"
            />
            <p class="schedule-editor-page__valid-from-hint">{{ t.validFromHint }} {{ t.validTo }}: {{ schedule.validTo }}.</p>
          </div>
        </div>
        <div class="schedule-editor-page__controls">
          <AppButton
            v-if="isReadOnly"
            variant="primary"
            data-testid="duplicate-schedule"
            @click="duplicateToDraft"
          >
            {{ t.duplicateSchedule }}
          </AppButton>
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
            <AppSelect
              id="schedule-lesson-type-filter"
              :label="t.lessonType"
              :model-value="selectedLessonType"
              :options="lessonTypeFilterOptions"
              @update:model-value="selectLessonType"
            />
            <AppSelect
              id="schedule-card-sort"
              :label="t.orderBy"
              :model-value="cardSort"
              :options="cardSortOptions"
              @update:model-value="selectCardSort"
            />
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
            :read-only="isReadOnly"
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
