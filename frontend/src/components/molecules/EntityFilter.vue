<script setup lang="ts">
import AppSelect from '@/components/atoms/AppSelect.vue'
import SegmentedControl from '@/components/atoms/SegmentedControl.vue'
import { labels } from '@/i18n/publicSchedule'
import type { LookupOption, PublicScheduleFilterType } from '@/types/publicSchedule'

defineProps<{
  type: PublicScheduleFilterType
  selectedId: number | null
  options: LookupOption[]
}>()

const emit = defineEmits<{
  'update:type': [value: PublicScheduleFilterType]
  'update:selectedId': [value: number]
}>()

const typeOptions = [
  { value: 'group' as const, label: labels.types.group },
  { value: 'teacher' as const, label: labels.types.teacher },
  { value: 'room' as const, label: labels.types.room },
]
</script>

<template>
  <div class="entity-filter">
    <SegmentedControl
      data-testid="entity-type"
      :label="labels.entityType"
      :model-value="type"
      :options="typeOptions"
      @update:model-value="emit('update:type', $event)"
    />
    <AppSelect
      id="entity-select"
      data-testid="entity-select"
      :label="labels.entity"
      :model-value="selectedId ?? ''"
      :options="options"
      @update:model-value="emit('update:selectedId', Number($event))"
    />
  </div>
</template>
