<script setup lang="ts">
defineOptions({ inheritAttrs: false })

defineProps<{
  label: string
  modelValue: number[]
  options: Array<{ id: number; label: string }>
  disabled?: boolean
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number[]]
}>()

function toggle(current: number[], id: number, checked: boolean): void {
  emit('update:modelValue', checked ? [...new Set([...current, id])] : current.filter((item) => item !== id))
}
</script>

<template>
  <fieldset
    v-bind="$attrs"
    class="field checkbox-group"
    :class="{ 'field--invalid': error }"
    :disabled="disabled"
  >
    <legend class="field__label">{{ label }}</legend>
    <div class="checkbox-group__options">
      <label v-for="option in options" :key="option.id" class="checkbox-option">
        <input
          type="checkbox"
          :checked="modelValue.includes(option.id)"
          :value="option.id"
          @change="toggle(modelValue, option.id, ($event.target as HTMLInputElement).checked)"
        />
        <span>{{ option.label }}</span>
      </label>
    </div>
    <small v-if="error" class="field-error">{{ error }}</small>
  </fieldset>
</template>
