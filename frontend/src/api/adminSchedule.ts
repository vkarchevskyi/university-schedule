import { requestJson } from '@/api/http'
import type {
  AdminRoom,
  AdminSchedule,
  AdminSemester,
  AdminGroup,
  AdminSubject,
  AdminTeacher,
  AdminTimeSlot,
  LessonCard,
  ResourceCollection,
  ScheduleEntryPayload,
  ScheduleGenerationJob,
  ScheduleValidationResult,
} from '@/types/adminSchedule'

export function listSemesters(): Promise<ResourceCollection<AdminSemester>> {
  return requestJson('/api/admin/semesters', { authenticated: true })
}

export function listRooms(): Promise<ResourceCollection<AdminRoom>> {
  return requestJson('/api/admin/rooms', { authenticated: true })
}

export function listTimeSlots(): Promise<ResourceCollection<AdminTimeSlot>> {
  return requestJson('/api/admin/time-slots', { authenticated: true })
}

export function listGroups(): Promise<ResourceCollection<AdminGroup>> {
  return requestJson('/api/admin/groups', { authenticated: true })
}

export function listTeachers(): Promise<ResourceCollection<AdminTeacher>> {
  return requestJson('/api/admin/teachers', { authenticated: true })
}

export function listSubjects(): Promise<ResourceCollection<AdminSubject>> {
  return requestJson('/api/admin/subjects', { authenticated: true })
}

export function listSchedules(semesterId?: number): Promise<ResourceCollection<AdminSchedule>> {
  const query = semesterId === undefined ? '' : `?semesterId=${semesterId}`

  return requestJson(`/api/admin/schedules${query}`, { authenticated: true })
}

export function createSchedule(payload: {
  semesterId: number
  validFrom: string
  validTo: string
}): Promise<AdminSchedule> {
  return requestJson('/api/admin/schedules', {
    method: 'POST',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function getSchedule(id: number): Promise<AdminSchedule> {
  return requestJson(`/api/admin/schedules/${id}`, { authenticated: true })
}

export function listLessonCards(scheduleId: number): Promise<ResourceCollection<LessonCard>> {
  return requestJson(`/api/admin/schedules/${scheduleId}/lesson-cards`, { authenticated: true })
}

export function createScheduleEntry(
  scheduleId: number,
  payload: ScheduleEntryPayload,
): Promise<unknown> {
  return requestJson(`/api/admin/schedules/${scheduleId}/entries`, {
    method: 'POST',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function updateScheduleEntry(
  scheduleId: number,
  entryId: number,
  payload: Partial<ScheduleEntryPayload>,
): Promise<unknown> {
  return requestJson(`/api/admin/schedules/${scheduleId}/entries/${entryId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function deleteScheduleEntry(scheduleId: number, entryId: number): Promise<void> {
  return requestJson(`/api/admin/schedules/${scheduleId}/entries/${entryId}`, {
    method: 'DELETE',
    authenticated: true,
  })
}

export function validateSchedule(scheduleId: number): Promise<ScheduleValidationResult> {
  return requestJson(`/api/admin/schedules/${scheduleId}/validate`, {
    method: 'POST',
    authenticated: true,
  })
}

export function publishSchedule(scheduleId: number): Promise<AdminSchedule> {
  return requestJson(`/api/admin/schedules/${scheduleId}/publish`, {
    method: 'POST',
    authenticated: true,
  })
}

export function generateSchedule(
  semesterId: number,
  scheduleId?: number,
): Promise<ScheduleGenerationJob> {
  const body: { semesterId: number; scheduleId?: number } = { semesterId }
  if (scheduleId !== undefined) {
    body.scheduleId = scheduleId
  }

  return requestJson('/api/admin/schedules/generate', {
    method: 'POST',
    body: JSON.stringify(body),
    authenticated: true,
  })
}

export function getGenerationJob(jobId: string): Promise<ScheduleGenerationJob> {
  return requestJson(`/api/admin/generation-jobs/${jobId}`, { authenticated: true })
}

export function listGenerationJobs(): Promise<ResourceCollection<ScheduleGenerationJob>> {
  return requestJson('/api/admin/generation-jobs', { authenticated: true })
}
