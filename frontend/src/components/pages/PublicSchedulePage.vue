<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import DesktopScheduleGrid from '@/components/organisms/DesktopScheduleGrid.vue'
import MobileScheduleList from '@/components/organisms/MobileScheduleList.vue'
import PublicScheduleToolbar from '@/components/organisms/PublicScheduleToolbar.vue'
import { usePublicSchedule } from '@/composables/usePublicSchedule'
import { labels } from '@/i18n/publicSchedule'

const {
  filterType,
  selectedId,
  weekStart,
  lookupOptions,
  items,
  hasLookups,
  isLoadingLookups,
  isLoadingSchedule,
  error,
  loadSchedule,
} = usePublicSchedule()
</script>

<template>
  <main class="public-page">
    <header class="public-page__header">
      <div>
        <h1>{{ labels.title }}</h1>
        <p>{{ labels.subtitle }}</p>
      </div>
    </header>

    <StateMessage v-if="isLoadingLookups" :title="labels.loadingLookups" />
    <StateMessage v-else-if="!hasLookups" :title="labels.noLookups" />
    <template v-else>
      <PublicScheduleToolbar
        v-model:type="filterType"
        v-model:selected-id="selectedId"
        v-model:week-start="weekStart"
        :options="lookupOptions"
      />

      <StateMessage v-if="error" tone="error" :title="error" data-testid="error-state">
        <AppButton variant="primary" data-testid="retry-button" @click="loadSchedule">
          {{ labels.retry }}
        </AppButton>
      </StateMessage>
      <StateMessage
        v-else-if="isLoadingSchedule"
        :title="labels.loading"
        data-testid="loading-state"
      />
      <StateMessage
        v-else-if="items.length === 0"
        :title="labels.empty"
        data-testid="empty-state"
      />
      <section v-else class="schedule-section" aria-label="Weekly schedule">
        <DesktopScheduleGrid :week-start="weekStart" :items="items" />
        <MobileScheduleList :week-start="weekStart" :items="items" />
      </section>
    </template>
  </main>
</template>
