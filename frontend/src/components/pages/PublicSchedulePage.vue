<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import { getPublicSchedule, listPublicGroups, listPublicRooms, listPublicTeachers } from '@/api/publicSchedule'
import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import DesktopScheduleGrid from '@/components/organisms/DesktopScheduleGrid.vue'
import MobileScheduleList from '@/components/organisms/MobileScheduleList.vue'
import PublicScheduleToolbar from '@/components/organisms/PublicScheduleToolbar.vue'
import { labels } from '@/i18n/publicSchedule'
import type {
  LookupOption,
  PublicGroup,
  PublicRoom,
  PublicSchedule,
  PublicScheduleFilterType,
  PublicTeacher,
} from '@/types/publicSchedule'
import { currentWeekStart } from '@/utils/date'

const filterType = ref<PublicScheduleFilterType>('group')
const selectedId = ref<number | null>(null)
const weekStart = ref(currentWeekStart())
const groups = ref<PublicGroup[]>([])
const teachers = ref<PublicTeacher[]>([])
const rooms = ref<PublicRoom[]>([])
const schedule = ref<PublicSchedule | null>(null)
const isLoadingLookups = ref(true)
const isLoadingSchedule = ref(false)
const error = ref<string | null>(null)

const lookupOptions = computed<LookupOption[]>(() => {
  if (filterType.value === 'teacher') {
    return teachers.value.map((teacher) => ({
      id: teacher.id,
      label: `${teacher.firstName} ${teacher.lastName}`,
      description: teacher.department,
    }))
  }

  if (filterType.value === 'room') {
    return rooms.value.map((room) => ({
      id: room.id,
      label: room.name,
      description: `${room.type}, ${room.capacity}`,
    }))
  }

  return groups.value.map((group) => ({
    id: group.id,
    label: group.name,
    description: `${group.speciality}, ${group.course}`,
  }))
})

const items = computed(() => schedule.value?.items ?? [])
const hasLookups = computed(() => groups.value.length > 0 || teachers.value.length > 0 || rooms.value.length > 0)

onMounted(async () => {
  await loadLookups()
})

watch(filterType, () => {
  selectedId.value = lookupOptions.value[0]?.id ?? null
})

watch([filterType, selectedId, weekStart], async () => {
  await loadSchedule()
})

async function loadLookups(): Promise<void> {
  isLoadingLookups.value = true
  error.value = null

  try {
    const [groupResponse, teacherResponse, roomResponse] = await Promise.all([
      listPublicGroups(),
      listPublicTeachers(),
      listPublicRooms(),
    ])

    groups.value = groupResponse.items
    teachers.value = teacherResponse.items
    rooms.value = roomResponse.items
    selectedId.value = lookupOptions.value[0]?.id ?? null
    await loadSchedule()
  } catch {
    error.value = labels.error
  } finally {
    isLoadingLookups.value = false
  }
}

async function loadSchedule(): Promise<void> {
  if (selectedId.value === null) {
    schedule.value = null
    return
  }

  isLoadingSchedule.value = true
  error.value = null

  try {
    schedule.value = await getPublicSchedule(filterType.value, selectedId.value, weekStart.value)
  } catch {
    error.value = labels.error
  } finally {
    isLoadingSchedule.value = false
  }
}
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
      <StateMessage v-else-if="isLoadingSchedule" :title="labels.loading" data-testid="loading-state" />
      <StateMessage v-else-if="items.length === 0" :title="labels.empty" data-testid="empty-state" />
      <section v-else class="schedule-section" aria-label="Weekly schedule">
        <DesktopScheduleGrid :week-start="weekStart" :items="items" />
        <MobileScheduleList :week-start="weekStart" :items="items" />
      </section>
    </template>
  </main>
</template>
