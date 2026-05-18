<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

import { cn } from '@/lib/utils'

defineOptions({ name: 'UiLabel' })

defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <label :class="cn('text-sm font-semibold leading-none text-muted-foreground', $props.class)">
    <slot />
  </label>
</template>
