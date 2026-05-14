import { requestJson } from '@/api/http'
import type {
  AdminRoom,
  AdminSchedule,
  AdminSemester,
  AdminTimeSlot,
  LessonCard,
  ResourceCollection,
  ScheduleEntryPayload,
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

export function createScheduleEntry(scheduleId: number, payload: ScheduleEntryPayload): Promise<unknown> {
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
