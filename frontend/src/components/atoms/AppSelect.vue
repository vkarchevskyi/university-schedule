<script setup lang="ts">
import type { LookupOption } from '@/types/publicSchedule'

defineProps<{
  id: string
  label: string
  modelValue: string | number
  options: LookupOption[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <label class="field" :for="id">
    <span class="field__label">{{ label }}</span>
    <select
      :id="id"
      class="field__control"
      :value="modelValue"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-for="option in options" :key="option.id" :value="option.id">
        {{ option.label }}
        <template v-if="option.description"> - {{ option.description }}</template>
      </option>
    </select>
  </label>
</template>
