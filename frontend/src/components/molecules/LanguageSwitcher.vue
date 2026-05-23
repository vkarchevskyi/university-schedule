<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { useLocaleStore } from '@/stores/locale'

defineProps<{
  label: string
}>()

const locale = useLocaleStore()
</script>

<template>
  <div class="language-switcher">
    <span>{{ label }}</span>
    <AppButton
      variant="ghost"
      data-testid="language-switcher"
      :aria-label="label"
      @click="locale.toggleLocale"
    >
      {{ locale.languageLabel }}
    </AppButton>
  </div>
</template>
