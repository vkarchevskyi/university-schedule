<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'

import { cn } from '@/lib/utils'

defineOptions({ name: 'UiAlert' })

const alertVariants = cva('relative grid gap-2 rounded-lg border p-6 text-sm shadow-sm', {
  variants: {
    variant: {
      default: 'border-border bg-card text-card-foreground',
      destructive: 'border-destructive/50 bg-destructive/10 text-destructive',
    },
  },
  defaultVariants: {
    variant: 'default',
  },
})

defineProps<{
  variant?: VariantProps<typeof alertVariants>['variant']
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div role="alert" :class="cn(alertVariants({ variant }), $props.class)">
    <slot />
  </div>
</template>
