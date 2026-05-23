import { computed } from 'vue'

import { adminLabels } from '@/i18n/admin'
import { publicScheduleLabels } from '@/i18n/publicSchedule'
import { useLocaleStore } from '@/stores/locale'

export function useAdminI18n() {
  const locale = useLocaleStore()
  const t = computed(() => adminLabels[locale.locale] ?? adminLabels[locale.fallback])

  return {
    t,
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
