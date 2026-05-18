<script setup lang="ts">
import { Alert, AlertTitle } from '@/components/ui/alert'

defineProps<{
  tone?: 'neutral' | 'error'
  title: string
}>()
</script>

<template>
  <Alert :variant="tone === 'error' ? 'destructive' : 'default'">
    <AlertTitle>{{ title }}</AlertTitle>
    <slot />
  </Alert>
</template>
