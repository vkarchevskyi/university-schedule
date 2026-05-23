import type { AdminGroup, AdminRoom, AdminSubject, AdminTeacher } from '@/types/adminSchedule'

export interface ResourceCollection<T> {
  items: T[]
}

export type ExamEntryType = 'consultation' | 'exam'

export interface ExamScheduleEntry {
  id: number
  examScheduleId: number
  type: ExamEntryType
  subjectId: number
  teacherId: number
  roomId: number
  entryDate: string
  startsAt: string
  groupIds: number[]
  deletedAt: string | null
}

export interface ExamSchedule {
  id: number
  semesterId: number
  status: string
  createdBy: number
  createdAt: string
  publishedAt: string | null
  deletedAt: string | null
  entries: ExamScheduleEntry[]
}

export interface ExamScheduleEntryPayload {
  type: ExamEntryType
  subjectId: number
  teacherId: number
  roomId: number
  groupIds: number[]
  entryDate: string
  startsAt: string
}

export interface ExamConflict {
  type: string
  message: string
  entryIds: number[]
}

export interface ExamValidationResult {
  valid: boolean
  conflicts: ExamConflict[]
}

export interface ExamGenerationJob {
  id: string
  semesterId: number
  requestedBy: number
  status: 'queued' | 'running' | 'completed' | 'failed'
  generatedExamScheduleId: number | null
  qualityScore: number | null
  qualityStatus: string | null
  errorMessage: string | null
  diagnostics: Record<string, unknown> | null
  createdAt: string
  startedAt: string | null
  finishedAt: string | null
}

export interface ExamLookups {
  groups: AdminGroup[]
  rooms: AdminRoom[]
  subjects: AdminSubject[]
  teachers: AdminTeacher[]
}
