import { requestJson } from '@/api/http'
import type {
  PublicGroup,
  PublicRoom,
  PublicSchedule,
  PublicScheduleFilterType,
  PublicTeacher,
  ResourceCollection,
} from '@/types/publicSchedule'

export async function listPublicGroups(): Promise<ResourceCollection<PublicGroup>> {
  return requestJson('/api/public/groups')
}

export async function listPublicTeachers(): Promise<ResourceCollection<PublicTeacher>> {
  return requestJson('/api/public/teachers')
}

export async function listPublicRooms(): Promise<ResourceCollection<PublicRoom>> {
  return requestJson('/api/public/rooms')
}

export async function getPublicSchedule(
  type: PublicScheduleFilterType,
  id: number,
  weekStart: string,
): Promise<PublicSchedule> {
  const params = new URLSearchParams({
    type,
    id: String(id),
    weekStart,
  })

  return requestJson(`/api/public/schedule?${params.toString()}`)
}
