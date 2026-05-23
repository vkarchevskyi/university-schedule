<script setup lang="ts">
import { ref } from 'vue'

import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import TextInput from '@/components/atoms/TextInput.vue'
import { useAdminI18n } from '@/composables/useI18n'

defineProps<{
  isLoading: boolean
  error: string | null
}>()

const emit = defineEmits<{
  submit: [payload: { email: string; password: string }]
}>()

const email = ref('')
const password = ref('')
const { t } = useAdminI18n()

function submit(): void {
  emit('submit', { email: email.value, password: password.value })
}
</script>

<template>
  <form class="login-form" data-testid="login-form" @submit.prevent="submit">
    <TextInput
      id="admin-email"
      v-model="email"
      :label="t.email"
      type="email"
      autocomplete="email"
      required
    />
    <TextInput
      id="admin-password"
      v-model="password"
      :label="t.password"
      type="password"
      autocomplete="current-password"
      required
    />
    <StateMessage
      v-if="error"
      tone="error"
      :title="t.invalidCredentials"
      data-testid="login-error"
    />
    <AppButton variant="primary" type="submit" :disabled="isLoading" data-testid="login-submit">
      {{ t.login }}
    </AppButton>
  </form>
</template>
