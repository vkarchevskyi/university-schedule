import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { getCurrentUser, loginAdmin } from '@/api/auth'
import {
  clearStoredToken,
  getStoredToken,
  setStoredToken,
  setUnauthorizedHandler,
} from '@/api/http'
import type { UserProfile } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getStoredToken())
  const user = ref<UserProfile | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const isAuthenticated = computed(() => token.value !== null && user.value !== null)

  setUnauthorizedHandler(() => {
    clearSession()
  })

  async function login(email: string, password: string): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const response = await loginAdmin(email, password)
      token.value = response.token
      user.value = response.user
      setStoredToken(response.token)
    } catch {
      clearSession()
      error.value = 'invalid_credentials'
      throw new Error('invalid_credentials')
    } finally {
      isLoading.value = false
    }
  }

  async function loadCurrentUser(): Promise<void> {
    if (token.value === null) {
      return
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await getCurrentUser()
      user.value = response.user
    } catch {
      clearSession()
      throw new Error('unauthenticated')
    } finally {
      isLoading.value = false
    }
  }

  function logout(): void {
    clearSession()
  }

  function clearSession(): void {
    token.value = null
    user.value = null
    clearStoredToken()
  }

  return {
    token,
    user,
    isLoading,
    error,
    isAuthenticated,
    login,
    logout,
    loadCurrentUser,
  }
})
