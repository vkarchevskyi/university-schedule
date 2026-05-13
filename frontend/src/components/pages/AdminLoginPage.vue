<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import LoginForm from '@/components/molecules/LoginForm.vue'
import { adminCopy } from '@/i18n/admin'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

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
        <h1>{{ adminCopy.loginTitle }}</h1>
        <p>{{ adminCopy.loginSubtitle }}</p>
      </div>
      <LoginForm :is-loading="auth.isLoading" :error="auth.error" @submit="login" />
    </section>
  </main>
</template>
