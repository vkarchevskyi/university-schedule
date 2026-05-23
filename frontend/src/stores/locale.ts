import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { defaultLocale, fallbackLocale, locales, type Locale } from '@/types/locale'

const storageKey = 'university-schedule.locale'

function readInitialLocale(): Locale {
  const stored = window.localStorage.getItem(storageKey)
  return locales.includes(stored as Locale) ? (stored as Locale) : defaultLocale
}

export const useLocaleStore = defineStore('locale', () => {
  const locale = ref<Locale>(readInitialLocale())
  const fallback = ref<Locale>(fallbackLocale)

  const languageLabel = computed(() => locale.value.toUpperCase())

  function setLocale(nextLocale: Locale): void {
    locale.value = nextLocale
    window.localStorage.setItem(storageKey, nextLocale)
  }

  function toggleLocale(): void {
    setLocale(locale.value === 'uk' ? 'en' : 'uk')
  }

  return {
    locale,
    fallback,
    languageLabel,
    setLocale,
    toggleLocale,
  }
})
