<script setup lang="ts">
import type { PublicScheduleFilterType } from '@/types/publicSchedule'

defineProps<{
  label: string
  modelValue: PublicScheduleFilterType
  options: Array<{ value: PublicScheduleFilterType; label: string }>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: PublicScheduleFilterType]
}>()
</script>

<template>
  <fieldset class="segmented-control">
    <legend class="field__label">{{ label }}</legend>
    <button
      v-for="option in options"
      :key="option.value"
      class="segmented-control__button"
      :class="{ 'segmented-control__button--active': option.value === modelValue }"
      type="button"
      :aria-pressed="option.value === modelValue"
      @click="emit('update:modelValue', option.value)"
    >
      {{ option.label }}
    </button>
  </fieldset>
</template>
