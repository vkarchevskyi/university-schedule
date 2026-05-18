<script setup lang="ts">
import { Badge } from '@/components/ui/badge'

defineProps<{
  tone: 'warning' | 'info'
}>()
</script>

<template>
  <Badge :variant="tone">
    <slot />
  </Badge>
</template>
