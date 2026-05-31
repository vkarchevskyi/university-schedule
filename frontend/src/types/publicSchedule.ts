export type PublicScheduleFilterType = 'group' | 'teacher' | 'room'
export type PublicRoomType = 'lecture' | 'computer'

export interface PublicGroup {
  id: number
  name: string
  speciality: string
  course: number
  studentCount: number
}

export interface PublicTeacher {
  id: number
  firstName: string
  lastName: string
  department: string
}

export interface PublicRoom {
  id: number
  name: string
  type: PublicRoomType
  capacity: number
}

export interface ResourceCollection<T> {
  items: T[]
}

export interface ScheduleTimeSlot {
  id: number
  number: number
  startsAt: string
  endsAt: string
}

export interface ScheduleSubject {
  id: number
  name: string
}

export interface ScheduleTeacher {
  id: number
  firstName: string
  lastName: string
}

export interface ScheduleRoom {
  id: number
  name: string
  type: PublicRoomType
}

export interface ScheduleGroup {
  id: number
  name: string
}

export interface ScheduleItem {
  id: number
  date: string
  dayOfWeek: number
  lessonType: string
  timeSlot: ScheduleTimeSlot
  subject: ScheduleSubject
  teacher: ScheduleTeacher
  room: ScheduleRoom
  groups: ScheduleGroup[]
  isCancelled: boolean
  isOverride: boolean
}

export interface PublicSchedule {
  weekStart: string
  type: PublicScheduleFilterType
  id: number
  items: ScheduleItem[]
}

export interface LookupOption {
  id: number
  label: string
  description: string
}
