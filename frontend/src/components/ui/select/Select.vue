<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectRoot, SelectValue, SelectTrigger, SelectContent, SelectItem, SelectItemText, SelectViewport } from 'reka-ui'

import { cn } from '@/lib/utils'

defineOptions({ inheritAttrs: false })

defineProps<{
  modelValue: string | number
  options: Array<{ id: string | number; label: string; description?: string }>
  placeholder?: string
  class?: HTMLAttributes['class']
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <SelectRoot :model-value="String(modelValue)" @update:model-value="emit('update:modelValue', String($event))">
    <SelectTrigger
      v-bind="$attrs"
      :class="
        cn(
          'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-left text-sm text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
          $props.class,
        )
      "
    >
      <SelectValue :placeholder="placeholder" />
    </SelectTrigger>
    <SelectContent
      position="popper"
      :class="
        cn(
          'z-50 min-w-[var(--reka-select-trigger-width)] overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md',
        )
      "
    >
      <SelectViewport class="p-1">
        <SelectItem
          v-for="option in options"
          :key="option.id"
          :value="String(option.id)"
          class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
        >
          <SelectItemText>
            {{ option.label }}
            <template v-if="option.description"> - {{ option.description }}</template>
          </SelectItemText>
        </SelectItem>
      </SelectViewport>
    </SelectContent>
  </SelectRoot>
</template>
