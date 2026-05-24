import {
  buildNotificationsWebSocketUrl,
  createWebSocketTicket,
} from '@/api/adminNotifications'
import type { ExamGenerationJob } from '@/types/adminExamSchedule'
import type { ScheduleGenerationJob } from '@/types/adminSchedule'

export type GenerationNotificationType =
  | 'schedule_generation_job'
  | 'exam_schedule_generation_job'

export interface GenerationNotification<TJob> {
  type: GenerationNotificationType
  jobId: string
  status: TJob extends ScheduleGenerationJob | ExamGenerationJob ? TJob['status'] : string
  job: TJob
}

export function subscribeToGenerationJob<TJob>(
  type: GenerationNotificationType,
  jobId: string,
  onMessage: (message: GenerationNotification<TJob>) => void,
  onFallback: () => void,
): () => void {
  let settled = false
  let socket: WebSocket | null = null

  function fallback(): void {
    if (settled) {
      return
    }
    settled = true
    socket?.close()
    onFallback()
  }

  createWebSocketTicket()
    .then(({ ticket }) => {
      if (settled) {
        return
      }

      socket = new WebSocket(buildNotificationsWebSocketUrl(ticket))
      socket.addEventListener('open', () => {
        socket?.send(JSON.stringify({ action: 'subscribe', type, jobId }))
      })
      socket.addEventListener('message', (event) => {
        const message = JSON.parse(String(event.data)) as GenerationNotification<TJob>
        if (message.type === type && message.jobId === jobId) {
          onMessage(message)
        }
      })
      socket.addEventListener('error', fallback)
      socket.addEventListener('close', () => {
        if (!settled) {
          fallback()
        }
      })
    })
    .catch(fallback)

  return () => {
    settled = true
    socket?.close()
  }
}
