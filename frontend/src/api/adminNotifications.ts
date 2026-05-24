import { buildWebSocketUrl, requestJson } from '@/api/http'

export interface WebSocketTicket {
  ticket: string
  expiresAt: string
}

export function createWebSocketTicket(): Promise<WebSocketTicket> {
  return requestJson('/api/admin/notifications/ws-ticket', {
    method: 'POST',
    authenticated: true,
  })
}

export function buildNotificationsWebSocketUrl(ticket: string): string {
  return buildWebSocketUrl('/api/admin/notifications/ws', { ticket })
}
