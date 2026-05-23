import { onMounted, ref } from 'vue'

import { listActionLogs } from '@/api/adminActionLog'
import { useAdminI18n } from '@/composables/useI18n'
import type { AdminActionLog } from '@/types/adminActionLog'

export function useAdminActionLogs() {
  const { t } = useAdminI18n()
  const logs = ref<AdminActionLog[]>([])
  const isLoading = ref(true)
  const error = ref<string | null>(null)

  onMounted(load)

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const response = await listActionLogs()
      logs.value = response.items
    } catch {
      error.value = t.value.apiError
    } finally {
      isLoading.value = false
    }
  }

  return {
    logs,
    isLoading,
    error,
    load,
  }
}
