<script setup lang="ts">
import AppSelect from '@/components/atoms/AppSelect.vue'
import SegmentedControl from '@/components/atoms/SegmentedControl.vue'
import { usePublicScheduleI18n } from '@/composables/useI18n'
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

const { t: labels } = usePublicScheduleI18n()
</script>

<template>
  <div class="entity-filter">
    <SegmentedControl
      data-testid="entity-type"
      :label="labels.entityType"
      :model-value="type"
      :options="[
        { value: 'group', label: labels.types.group },
        { value: 'teacher', label: labels.types.teacher },
        { value: 'room', label: labels.types.room },
      ]"
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
