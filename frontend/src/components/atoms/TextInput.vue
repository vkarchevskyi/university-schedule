<script setup lang="ts">
defineProps<{
  id: string
  label: string
  modelValue: string
  type?: 'email' | 'password' | 'text'
  autocomplete?: string
  required?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <label class="field" :for="id">
    <span class="field__label">{{ label }}</span>
    <input
      :id="id"
      class="field__control"
      :type="type ?? 'text'"
      :value="modelValue"
      :autocomplete="autocomplete"
      :required="required"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
  </label>
</template>
