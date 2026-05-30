<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { useAdminI18n } from '@/composables/useI18n'

defineProps<{
  title: string
  message?: string | null
  details?: string[]
}>()

const emit = defineEmits<{
  close: []
}>()

const { t } = useAdminI18n()
</script>

<template>
  <div class="modal-backdrop" data-testid="error-modal">
    <section class="modal-panel error-dialog" role="alertdialog" aria-modal="true">
      <header class="modal-panel__header">
        <h2>{{ title }}</h2>
      </header>
      <p v-if="message">{{ message }}</p>
      <ul v-if="details && details.length > 0" class="error-dialog__details">
        <li v-for="detail in details" :key="detail">{{ detail }}</li>
      </ul>
      <footer class="modal-panel__footer">
        <AppButton variant="primary" data-testid="error-modal-close" @click="emit('close')">
          {{ t.close }}
        </AppButton>
      </footer>
    </section>
  </div>
</template>
