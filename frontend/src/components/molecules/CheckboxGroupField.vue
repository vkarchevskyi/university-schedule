<script setup lang="ts">
defineProps<{
  label: string
  modelValue: number[]
  options: Array<{ id: number; label: string }>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number[]]
}>()

function toggle(current: number[], id: number, checked: boolean): void {
  emit('update:modelValue', checked ? [...new Set([...current, id])] : current.filter((item) => item !== id))
}
</script>

<template>
  <fieldset class="field checkbox-group">
    <legend class="field__label">{{ label }}</legend>
    <label v-for="option in options" :key="option.id" class="checkbox-option">
      <input
        type="checkbox"
        :checked="modelValue.includes(option.id)"
        :value="option.id"
        @change="toggle(modelValue, option.id, ($event.target as HTMLInputElement).checked)"
      />
      <span>{{ option.label }}</span>
    </label>
  </fieldset>
</template>
