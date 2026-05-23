import { requestJson } from '@/api/http'
import type { AdminActionLog } from '@/types/adminActionLog'
import type { ResourceCollection } from '@/types/adminSchedule'

export function listActionLogs(): Promise<ResourceCollection<AdminActionLog>> {
  return requestJson('/api/admin/action-logs', { authenticated: true })
}
