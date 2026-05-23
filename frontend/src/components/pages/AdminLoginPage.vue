<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import LoginForm from '@/components/molecules/LoginForm.vue'
import LanguageSwitcher from '@/components/molecules/LanguageSwitcher.vue'
import { useAdminI18n } from '@/composables/useI18n'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const { t } = useAdminI18n()

onMounted(async () => {
  if (auth.isAuthenticated) {
    await router.replace({ name: 'admin-dashboard' })
  }
})

async function login(payload: { email: string; password: string }): Promise<void> {
  try {
    await auth.login(payload.email, payload.password)
    await router.push({ name: 'admin-dashboard' })
  } catch {
    // The store exposes the localized error state to the form.
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-panel">
      <div>
        <h1>{{ t.loginTitle }}</h1>
        <p>{{ t.loginSubtitle }}</p>
      </div>
      <LanguageSwitcher :label="t.language" />
      <LoginForm :is-loading="auth.isLoading" :error="auth.error" @submit="login" />
    </section>
  </main>
</template>
