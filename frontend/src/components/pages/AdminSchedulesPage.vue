<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminSchedules } from '@/composables/useAdminSchedules'
import { adminCopy } from '@/i18n/admin'

const { schedules, selectedSemesterId, isLoading, error, semesterOptions, createDraft, openSchedule } =
  useAdminSchedules()
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard">
      <h1>{{ adminCopy.schedulesTitle }}</h1>
      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" :title="adminCopy.loading" />
      <template v-else>
        <div class="schedule-create">
          <AppSelect
            id="schedule-semester"
            :label="adminCopy.semester"
            :model-value="selectedSemesterId ?? ''"
            :options="semesterOptions"
            @update:model-value="selectedSemesterId = Number($event)"
          />
          <AppButton variant="primary" data-testid="create-schedule" @click="createDraft">
            {{ adminCopy.createSchedule }}
          </AppButton>
        </div>
        <StateMessage v-if="schedules.length === 0" :title="adminCopy.noSchedules" data-testid="no-schedules" />
        <div v-else class="schedule-list" data-testid="schedule-list">
          <article v-for="schedule in schedules" :key="schedule.id" class="schedule-list__item">
            <div>
              <strong>#{{ schedule.id }} · {{ schedule.status }}</strong>
              <span>{{ schedule.validFrom }} - {{ schedule.validTo }}</span>
            </div>
            <AppButton
              variant="secondary"
              data-testid="open-schedule"
              @click="openSchedule(schedule.id)"
            >
              {{ adminCopy.openSchedule }}
            </AppButton>
          </article>
        </div>
      </template>
    </section>
  </AdminLayout>
</template>
