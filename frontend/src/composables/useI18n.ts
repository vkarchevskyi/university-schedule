import { computed } from 'vue'

import { adminLabels } from '@/i18n/admin'
import { publicScheduleLabels } from '@/i18n/publicSchedule'
import { useLocaleStore } from '@/stores/locale'

export function useAdminI18n() {
  const locale = useLocaleStore()
  const t = computed(() => adminLabels[locale.locale] ?? adminLabels[locale.fallback])
  const label = (key: string): string => labelFrom(t.value, key)

  return {
    t,
    label,
    locale,
  }
}

export function usePublicScheduleI18n() {
  const locale = useLocaleStore()
  const t = computed(() => publicScheduleLabels[locale.locale] ?? publicScheduleLabels[locale.fallback])

  return {
    t,
    locale,
  }
}

function labelFrom(source: Record<string, unknown>, key: string): string {
  const value = key.split('.').reduce<unknown>((current, segment) => {
    if (current !== null && typeof current === 'object' && segment in current) {
      return (current as Record<string, unknown>)[segment]
    }

    return undefined
  }, source)

  return typeof value === 'string' ? value : key
}
