<script setup lang="ts">
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
  <button class="app-button" :class="`app-button--${variant}`" :type="type" :disabled="disabled">
    <slot />
  </button>
</template>
