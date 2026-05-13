<script setup lang="ts">
defineProps<{
  tone?: 'neutral' | 'error'
  title: string
}>()
</script>

<template>
  <div class="state-message" :class="`state-message--${tone ?? 'neutral'}`">
    <p>{{ title }}</p>
    <slot />
  </div>
</template>
