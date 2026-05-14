<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { createSchedule, listSchedules, listSemesters } from '@/api/adminSchedule'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { adminCopy } from '@/i18n/admin'
import type { AdminSchedule, AdminSemester } from '@/types/adminSchedule'

const router = useRouter()
const schedules = ref<AdminSchedule[]>([])
const semesters = ref<AdminSemester[]>([])
const selectedSemesterId = ref<number | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

const semesterOptions = computed(() =>
  semesters.value.map((semester) => ({
    id: semester.id,
    label: `Семестр ${semester.number}`,
    description: `${semester.startsAt} - ${semester.endsAt}`,
  })),
)

onMounted(load)

async function load(): Promise<void> {
  isLoading.value = true
  error.value = null

  try {
    const [semesterResponse, scheduleResponse] = await Promise.all([listSemesters(), listSchedules()])
    semesters.value = semesterResponse.items
    schedules.value = scheduleResponse.items
    selectedSemesterId.value = semesterResponse.items[0]?.id ?? null
  } catch {
    error.value = adminCopy.apiError
  } finally {
    isLoading.value = false
  }
}

async function createDraft(): Promise<void> {
  const semester = semesters.value.find((item) => item.id === selectedSemesterId.value)
  if (!semester) {
    return
  }

  const schedule = await createSchedule({
    semesterId: semester.id,
    validFrom: semester.startsAt,
    validTo: semester.endsAt,
  })

  await router.push({ name: 'admin-schedule-editor', params: { id: schedule.id } })
}
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard">
      <h1>{{ adminCopy.schedulesTitle }}</h1>
      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" title="Завантаження..." />
      <template v-else>
        <div class="schedule-create">
          <AppSelect
            id="schedule-semester"
            label="Семестр"
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
              @click="router.push({ name: 'admin-schedule-editor', params: { id: schedule.id } })"
            >
              {{ adminCopy.openSchedule }}
            </AppButton>
          </article>
        </div>
      </template>
    </section>
  </AdminLayout>
</template>
