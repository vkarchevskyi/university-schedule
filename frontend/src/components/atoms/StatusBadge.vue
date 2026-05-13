<script setup lang="ts">
defineProps<{
  tone: 'warning' | 'info'
}>()
</script>

<template>
  <span class="status-badge" :class="`status-badge--${tone}`">
    <slot />
  </span>
</template>
