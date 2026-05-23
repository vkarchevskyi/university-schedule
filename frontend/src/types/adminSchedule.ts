export interface ResourceCollection<T> {
  items: T[]
}

export interface LookupOption {
  id: number
  label: string
  description: string
}

export interface AdminSemester {
  id: number
  academicYearId: number
  number: number
  startsAt: string
  endsAt: string
  firstWeekParity: 'odd' | 'even'
}

export interface AdminRoom {
  id: number
  name: string
  type: string
  capacity: number
}

export interface AdminGroup {
  id: number
  name: string
  speciality: string
  course: number
  studentCount: number
}

export interface AdminTeacher {
  id: number
  firstName: string
  lastName: string
  department: string
}

export interface AdminSubject {
  id: number
  name: string
}

export interface AdminTimeSlot {
  id: number
  number: number
  startsAt: string
  endsAt: string
}

export type LessonType = 'lecture' | 'laboratory' | 'seminar' | 'practical'
export type WeekParity = 'odd' | 'even' | 'both'

export interface AdminScheduleEntry {
  id: number
  scheduleId: number
  subjectId: number
  teacherId: number
  lessonType: LessonType
  roomId: number
  timeSlotId: number
  dayOfWeek: number
  weekParity: WeekParity
  groupIds: number[]
  teachingLoadIds: number[]
}

export interface AdminSchedule {
  id: number
  semesterId: number
  status: string
  validFrom: string
  validTo: string
  createdBy: number
  createdAt: string
  publishedAt: string | null
  entries: AdminScheduleEntry[]
}

export interface LessonCard {
  teachingLoadId: number
  group: { id: number; name: string }
  subject: { id: number; name: string }
  teacher: { id: number; firstName: string; lastName: string; department: string }
  lessonType: LessonType
  requiredLessonCount: number
  scheduledLessonCount: number
  remainingLessonCount: number
}

export interface ScheduleValidationConflict {
  type: string
  message: string
  entryIds: number[]
}

export interface ScheduleValidationResult {
  valid: boolean
  conflicts: ScheduleValidationConflict[]
}

export interface ScheduleGenerationJob {
  id: string
  semesterId: number
  requestedBy: number
  status: 'queued' | 'running' | 'completed' | 'failed'
  generatedScheduleId: number | null
  qualityScore: number | null
  qualityStatus: string | null
  errorMessage: string | null
  diagnostics: Record<string, unknown> | null
  createdAt: string
  startedAt: string | null
  finishedAt: string | null
}

export interface ScheduleEntryPayload {
  teachingLoadIds: number[]
  subjectId: number
  teacherId: number
  lessonType: LessonType
  roomId: number
  timeSlotId: number
  dayOfWeek: number
  weekParity: WeekParity
  groupIds: number[]
}
