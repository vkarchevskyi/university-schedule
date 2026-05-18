<script setup lang="ts">
import { useRoute } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import LessonRequirementCard from '@/components/molecules/LessonRequirementCard.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import ScheduleEntryEditor from '@/components/organisms/ScheduleEntryEditor.vue'
import ScheduleEntryGrid from '@/components/organisms/ScheduleEntryGrid.vue'
import { useAdminScheduleEditor } from '@/composables/useAdminScheduleEditor'
import { adminCopy } from '@/i18n/admin'

const route = useRoute()
const scheduleId = Number(route.params.id)
const {
  schedule,
  cards,
  rooms,
  timeSlots,
  selectedRoomId,
  selectedEntry,
  conflicts,
  message,
  error,
  isLoading,
  roomOptions,
  place,
  saveEntry,
  removeEntry,
  validate,
} = useAdminScheduleEditor(scheduleId)
</script>

<template>
  <AdminLayout>
    <StateMessage v-if="error" tone="error" :title="error" data-testid="editor-error" />
    <StateMessage v-else-if="isLoading" :title="adminCopy.loading" />
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
          <LessonRequirementCard
            v-for="card in cards"
            v-else
            :key="card.teachingLoadId"
            :card="card"
          />
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
