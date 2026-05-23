<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { useRoute } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import CheckboxGroupField from '@/components/molecules/CheckboxGroupField.vue'
import ConfirmActionButton from '@/components/molecules/ConfirmActionButton.vue'
import ConflictPanel from '@/components/molecules/ConflictPanel.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminExamScheduleEditor } from '@/composables/useAdminExamScheduleEditor'
import { useAdminI18n } from '@/composables/useI18n'
import type { ExamScheduleEntryPayload } from '@/types/adminExamSchedule'

const route = useRoute()
const scheduleId = Number(route.params.id)
const { t } = useAdminI18n()
const { schedule, lookups, selectedEntry, conflicts, message, error, isLoading, save, remove, validate } =
  useAdminExamScheduleEditor(scheduleId)

const form = reactive<ExamScheduleEntryPayload>({
  type: 'exam',
  subjectId: 0,
  teacherId: 0,
  roomId: 0,
  groupIds: [],
  entryDate: '',
  startsAt: '',
})

const conflictEntryIds = computed(() => conflicts.value.flatMap((conflict) => conflict.entryIds))

watch(selectedEntry, (entry) => {
  form.type = entry?.type ?? 'exam'
  form.subjectId = entry?.subjectId ?? lookups.value.subjects[0]?.id ?? 0
  form.teacherId = entry?.teacherId ?? lookups.value.teachers[0]?.id ?? 0
  form.roomId = entry?.roomId ?? lookups.value.rooms[0]?.id ?? 0
  form.groupIds = entry?.groupIds ?? [lookups.value.groups[0]?.id ?? 0].filter(Boolean)
  form.entryDate = entry?.entryDate ?? ''
  form.startsAt = entry?.startsAt ?? ''
})

watch(
  lookups,
  (value) => {
    if (selectedEntry.value !== null) {
      return
    }

    form.subjectId ||= value.subjects[0]?.id ?? 0
    form.teacherId ||= value.teachers[0]?.id ?? 0
    form.roomId ||= value.rooms[0]?.id ?? 0
    form.groupIds = form.groupIds.length > 0 ? form.groupIds : [value.groups[0]?.id ?? 0].filter(Boolean)
  },
  { deep: true },
)

function submit(): void {
  void save({ ...form, groupIds: form.groupIds.filter((id) => id > 0) })
}

function nameById<T extends { id: number; name?: string; firstName?: string; lastName?: string }>(
  items: T[],
  id: number,
): string {
  const item = items.find((candidate) => candidate.id === id)
  if (item === undefined) {
    return `#${id}`
  }

  return item.name ?? `${item.firstName} ${item.lastName}`
}
</script>

<template>
  <AdminLayout>
    <StateMessage v-if="error" tone="error" :title="error" />
    <StateMessage v-else-if="isLoading" :title="t.loading" />
    <section v-else-if="schedule" class="exam-editor-page">
      <header class="admin-page-header">
        <div>
          <h1>{{ t.examScheduleEditor }} #{{ schedule.id }}</h1>
          <p>{{ t.semester }} #{{ schedule.semesterId }}</p>
        </div>
        <AppButton variant="primary" data-testid="validate-exam-schedule" @click="validate">
          {{ t.validate }}
        </AppButton>
      </header>

      <StateMessage v-if="message" :title="message" data-testid="exam-validation-result">
      </StateMessage>
      <ConflictPanel :conflicts="conflicts" />

      <div class="exam-editor-layout">
        <div class="admin-table-wrap">
          <table class="admin-table" data-testid="exam-entry-table">
            <thead>
              <tr>
                <th>{{ t.date }}</th>
                <th>{{ t.startsAt }}</th>
                <th>{{ t.examType }}</th>
                <th>{{ t.subject }}</th>
                <th>{{ t.teacher }}</th>
                <th>{{ t.room }}</th>
                <th>{{ t.actions }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in schedule.entries" :key="entry.id" :class="{ 'row-conflict': conflictEntryIds.includes(entry.id) }">
                <td>{{ entry.entryDate }}</td>
                <td>{{ entry.startsAt }}</td>
                <td>{{ t.examTypeOptions[entry.type] }}</td>
                <td>{{ nameById(lookups.subjects, entry.subjectId) }}</td>
                <td>{{ nameById(lookups.teachers, entry.teacherId) }}</td>
                <td>{{ nameById(lookups.rooms, entry.roomId) }}</td>
                <td>
                  <div class="table-actions">
                    <AppButton data-testid="edit-exam-entry" @click="selectedEntry = entry">{{ t.edit }}</AppButton>
                    <ConfirmActionButton
                      :message="t.deleteConfirm"
                      testid="delete-exam-entry"
                      @confirm="remove(entry)"
                    >
                      {{ t.delete }}
                    </ConfirmActionButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <form class="entry-editor" data-testid="exam-entry-form" @submit.prevent="submit">
          <h2>{{ selectedEntry ? t.editExamEntry : t.newExamEntry }}</h2>
          <label class="field">
            <span class="field__label">{{ t.examType }}</span>
            <select v-model="form.type" class="field__control">
              <option value="consultation">{{ t.examTypeOptions.consultation }}</option>
              <option value="exam">{{ t.examTypeOptions.exam }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">{{ t.subject }}</span>
            <select v-model.number="form.subjectId" class="field__control">
              <option v-for="subject in lookups.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">{{ t.teacher }}</span>
            <select v-model.number="form.teacherId" class="field__control">
              <option v-for="teacher in lookups.teachers" :key="teacher.id" :value="teacher.id">{{ teacher.firstName }} {{ teacher.lastName }}</option>
            </select>
          </label>
          <CheckboxGroupField
            v-model="form.groupIds"
            :label="t.groups"
            :options="lookups.groups.map((group) => ({ id: group.id, label: group.name }))"
          />
          <label class="field">
            <span class="field__label">{{ t.room }}</span>
            <select v-model.number="form.roomId" class="field__control">
              <option v-for="room in lookups.rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">{{ t.date }}</span>
            <input v-model="form.entryDate" required type="date" class="field__control" />
          </label>
          <label class="field">
            <span class="field__label">{{ t.startsAt }}</span>
            <input v-model="form.startsAt" required type="time" class="field__control" />
          </label>
          <AppButton type="submit" variant="primary" data-testid="save-exam-entry">{{ t.save }}</AppButton>
        </form>
      </div>
    </section>
  </AdminLayout>
</template>
