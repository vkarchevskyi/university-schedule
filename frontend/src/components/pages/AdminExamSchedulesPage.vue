<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminExamSchedules } from '@/composables/useAdminExamSchedules'

const {
  schedules,
  generationJob,
  isLoading,
  error,
  createDraft,
  startGeneration,
  openSchedule,
} = useAdminExamSchedules()
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard">
      <header class="admin-page-header">
        <div>
          <h1>Іспити</h1>
          <p>Керуйте іспитовими розкладами та генерацією.</p>
        </div>
        <div class="header-actions">
          <AppButton variant="primary" data-testid="create-exam-schedule" @click="createDraft">
            Створити чернетку
          </AppButton>
          <AppButton data-testid="generate-exam-schedule" @click="startGeneration">
            Згенерувати
          </AppButton>
        </div>
      </header>

      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" title="Завантаження..." />
      <StateMessage
        v-if="generationJob"
        :title="`Статус генерації іспитів: ${generationJob.status}`"
        data-testid="exam-generation-job"
      >
        <p v-if="generationJob.qualityScore !== null">Оцінка якості: {{ generationJob.qualityScore }}</p>
        <p v-if="generationJob.errorMessage">{{ generationJob.errorMessage }}</p>
        <AppButton
          v-if="generationJob.generatedExamScheduleId !== null"
          data-testid="open-generated-exam-schedule"
          @click="openSchedule(generationJob.generatedExamScheduleId)"
        >
          Відкрити згенеровану чернетку
        </AppButton>
      </StateMessage>

      <div v-if="!isLoading" class="schedule-list" data-testid="exam-schedule-list">
        <article v-for="schedule in schedules" :key="schedule.id" class="schedule-list__item">
          <div>
            <strong>#{{ schedule.id }} · {{ schedule.status }}</strong>
            <span>Семестр #{{ schedule.semesterId }}</span>
          </div>
          <AppButton @click="openSchedule(schedule.id)">Відкрити</AppButton>
        </article>
      </div>
    </section>
  </AdminLayout>
</template>
