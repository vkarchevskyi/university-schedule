<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { useRoute } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminExamScheduleEditor } from '@/composables/useAdminExamScheduleEditor'
import type { ExamScheduleEntryPayload } from '@/types/adminExamSchedule'

const route = useRoute()
const scheduleId = Number(route.params.id)
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
  form.groupIds = [entry?.groupIds[0] ?? lookups.value.groups[0]?.id ?? 0].filter(Boolean)
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
    <StateMessage v-else-if="isLoading" title="Завантаження..." />
    <section v-else-if="schedule" class="exam-editor-page">
      <header class="admin-page-header">
        <div>
          <h1>Розклад іспитів #{{ schedule.id }}</h1>
          <p>Семестр #{{ schedule.semesterId }}</p>
        </div>
        <AppButton variant="primary" data-testid="validate-exam-schedule" @click="validate">
          Перевірити
        </AppButton>
      </header>

      <StateMessage v-if="message" :title="message" data-testid="exam-validation-result">
        <ul>
          <li v-for="conflict in conflicts" :key="`${conflict.type}-${conflict.message}`">
            {{ conflict.message }}
          </li>
        </ul>
      </StateMessage>

      <div class="exam-editor-layout">
        <div class="admin-table-wrap">
          <table class="admin-table" data-testid="exam-entry-table">
            <thead>
              <tr>
                <th>Дата</th>
                <th>Час</th>
                <th>Тип</th>
                <th>Предмет</th>
                <th>Викладач</th>
                <th>Аудиторія</th>
                <th>Дії</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in schedule.entries" :key="entry.id" :class="{ 'row-conflict': conflictEntryIds.includes(entry.id) }">
                <td>{{ entry.entryDate }}</td>
                <td>{{ entry.startsAt }}</td>
                <td>{{ entry.type }}</td>
                <td>{{ nameById(lookups.subjects, entry.subjectId) }}</td>
                <td>{{ nameById(lookups.teachers, entry.teacherId) }}</td>
                <td>{{ nameById(lookups.rooms, entry.roomId) }}</td>
                <td>
                  <div class="table-actions">
                    <AppButton data-testid="edit-exam-entry" @click="selectedEntry = entry">Редагувати</AppButton>
                    <AppButton variant="ghost" data-testid="delete-exam-entry" @click="remove(entry)">Видалити</AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <form class="entry-editor" data-testid="exam-entry-form" @submit.prevent="submit">
          <h2>{{ selectedEntry ? 'Редагувати іспит' : 'Новий іспит' }}</h2>
          <label class="field">
            <span class="field__label">Тип</span>
            <select v-model="form.type" class="field__control">
              <option value="consultation">Консультація</option>
              <option value="exam">Іспит</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Предмет</span>
            <select v-model.number="form.subjectId" class="field__control">
              <option v-for="subject in lookups.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Викладач</span>
            <select v-model.number="form.teacherId" class="field__control">
              <option v-for="teacher in lookups.teachers" :key="teacher.id" :value="teacher.id">{{ teacher.firstName }} {{ teacher.lastName }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Група</span>
            <select v-model.number="form.groupIds[0]" class="field__control">
              <option v-for="group in lookups.groups" :key="group.id" :value="group.id">{{ group.name }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Аудиторія</span>
            <select v-model.number="form.roomId" class="field__control">
              <option v-for="room in lookups.rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Дата</span>
            <input v-model="form.entryDate" required type="date" class="field__control" />
          </label>
          <label class="field">
            <span class="field__label">Початок</span>
            <input v-model="form.startsAt" required type="time" class="field__control" />
          </label>
          <AppButton type="submit" variant="primary" data-testid="save-exam-entry">Зберегти</AppButton>
        </form>
      </div>
    </section>
  </AdminLayout>
</template>
