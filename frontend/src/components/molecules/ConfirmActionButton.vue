<script setup lang="ts">
import { ref } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import { useAdminI18n } from '@/composables/useI18n'

withDefaults(
  defineProps<{
    message: string
    confirmLabel?: string
    cancelLabel?: string
    variant?: 'primary' | 'secondary' | 'ghost'
    testid?: string
  }>(),
  {
    confirmLabel: undefined,
    cancelLabel: undefined,
    variant: 'ghost',
    testid: undefined,
  },
)

const emit = defineEmits<{
  confirm: []
}>()

const { t } = useAdminI18n()
const isOpen = ref(false)

function confirm(): void {
  emit('confirm')
  isOpen.value = false
}
</script>

<template>
  <AppButton :variant="variant" :data-testid="testid" @click="isOpen = true">
    <slot />
  </AppButton>

  <div v-if="isOpen" class="modal-backdrop" data-testid="confirm-dialog">
    <section class="modal-panel confirm-dialog" role="alertdialog" aria-modal="true">
      <header class="modal-panel__header">
        <h2>{{ t.confirmTitle }}</h2>
      </header>
      <p>{{ message }}</p>
      <footer class="modal-panel__footer">
        <AppButton variant="ghost" data-testid="confirm-cancel" @click="isOpen = false">
          {{ cancelLabel ?? t.cancel }}
        </AppButton>
        <AppButton
          variant="primary"
          data-testid="confirm-submit"
          @click="confirm"
        >
          {{ confirmLabel ?? t.confirm }}
        </AppButton>
      </footer>
    </section>
  </div>
</template>
