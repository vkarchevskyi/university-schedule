<script setup lang="ts">
import { Label } from '@/components/ui/label'
import { Select } from '@/components/ui/select'
import type { LookupOption } from '@/types/publicSchedule'

defineProps<{
  id: string
  label: string
  modelValue: string | number
  options: LookupOption[]
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div :class="['field', { 'field--invalid': error }]">
    <Label :for="id">{{ label }}</Label>
    <Select
      :id="id"
      :model-value="modelValue"
      :options="options"
      @update:model-value="emit('update:modelValue', $event)"
    />
    <small v-if="error" class="field-error">{{ error }}</small>
  </div>
</template>
