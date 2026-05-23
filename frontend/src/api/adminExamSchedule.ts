import { requestJson } from '@/api/http'
import type { ResourceCollection } from '@/types/adminSchedule'
import type {
  ExamGenerationJob,
  ExamSchedule,
  ExamScheduleEntry,
  ExamScheduleEntryPayload,
  ExamValidationResult,
} from '@/types/adminExamSchedule'

export function listExamSchedules(semesterId?: number): Promise<ResourceCollection<ExamSchedule>> {
  const query = semesterId === undefined ? '' : `?semesterId=${semesterId}`
  return requestJson(`/api/admin/exam-schedules${query}`, { authenticated: true })
}

export function createExamSchedule(semesterId: number): Promise<ExamSchedule> {
  return requestJson('/api/admin/exam-schedules', {
    method: 'POST',
    body: JSON.stringify({ semesterId }),
    authenticated: true,
  })
}

export function getExamSchedule(id: number): Promise<ExamSchedule> {
  return requestJson(`/api/admin/exam-schedules/${id}`, { authenticated: true })
}

export function deleteExamSchedule(id: number): Promise<void> {
  return requestJson(`/api/admin/exam-schedules/${id}`, {
    method: 'DELETE',
    authenticated: true,
  })
}

export function createExamEntry(
  scheduleId: number,
  payload: ExamScheduleEntryPayload,
): Promise<ExamScheduleEntry> {
  return requestJson(`/api/admin/exam-schedules/${scheduleId}/entries`, {
    method: 'POST',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function updateExamEntry(
  scheduleId: number,
  entryId: number,
  payload: Partial<ExamScheduleEntryPayload>,
): Promise<ExamScheduleEntry> {
  return requestJson(`/api/admin/exam-schedules/${scheduleId}/entries/${entryId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function deleteExamEntry(scheduleId: number, entryId: number): Promise<void> {
  return requestJson(`/api/admin/exam-schedules/${scheduleId}/entries/${entryId}`, {
    method: 'DELETE',
    authenticated: true,
  })
}

export function validateExamSchedule(scheduleId: number): Promise<ExamValidationResult> {
  return requestJson(`/api/admin/exam-schedules/${scheduleId}/validate`, {
    method: 'POST',
    authenticated: true,
  })
}

export function generateExamSchedule(semesterId: number): Promise<ExamGenerationJob> {
  return requestJson('/api/admin/exam-schedules/generate', {
    method: 'POST',
    body: JSON.stringify({ semesterId }),
    authenticated: true,
  })
}

export function getExamGenerationJob(jobId: string): Promise<ExamGenerationJob> {
  return requestJson(`/api/admin/exam-schedule-generation-jobs/${jobId}`, { authenticated: true })
}
