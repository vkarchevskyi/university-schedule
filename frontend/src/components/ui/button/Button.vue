<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

import { cn } from '@/lib/utils'
import { buttonVariants, type ButtonVariants } from '.'

defineOptions({ name: 'UiButton', inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset'
    variant?: ButtonVariants['variant']
    size?: ButtonVariants['size']
    disabled?: boolean
    class?: HTMLAttributes['class']
  }>(),
  {
    type: 'button',
    variant: 'secondary',
    size: 'default',
    disabled: false,
  },
)
</script>

<template>
  <button
    v-bind="$attrs"
    :type="props.type"
    :disabled="props.disabled"
    :class="cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)"
  >
    <slot />
  </button>
</template>
