<script setup lang="ts">
import { Label } from '@/components/ui/label'
import { Select } from '@/components/ui/select'
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
  <div class="field">
    <Label :for="id">{{ label }}</Label>
    <Select
      :id="id"
      :model-value="modelValue"
      :options="options"
      @update:model-value="emit('update:modelValue', $event)"
    />
  </div>
</template>
