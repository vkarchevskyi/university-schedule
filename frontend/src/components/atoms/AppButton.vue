<script setup lang="ts">
import { Button } from '@/components/ui/button'

defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    type?: 'button' | 'submit'
    variant?: 'primary' | 'secondary' | 'ghost'
    disabled?: boolean
  }>(),
  {
    type: 'button',
    variant: 'secondary',
    disabled: false,
  },
)
</script>

<template>
  <Button
    v-bind="$attrs"
    :type="type"
    :variant="variant === 'primary' ? 'default' : variant"
    :disabled="disabled"
  >
    <slot />
  </Button>
</template>
