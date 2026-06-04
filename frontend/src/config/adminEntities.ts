import type { EntityConfig } from '@/types/adminEntities'
import { scheduleWeekdays } from '@/utils/date'

function text(value: unknown): string {
  return value === null || value === undefined ? '' : String(value)
}

function lookupLabel(value: unknown): string {
  return text(value)
}

function booleanLabel(value: unknown): string {
  return value === true ? 'yes' : 'no'
}

const weekdayOptions = scheduleWeekdays.map((value) => ({
  value,
  labelKey: `weekdays.${value}`,
}))

export const entityConfigs: EntityConfig[] = [
  {
    key: 'groups',
    titleKey: 'entities.groups.title',
    hintKey: 'entities.groups.hint',
    routeName: 'admin-groups',
    endpoint: '/api/admin/groups',
    fields: [
      { key: 'name', labelKey: 'entityFields.name', type: 'text', required: true },
      { key: 'speciality', labelKey: 'entityFields.speciality', type: 'text', required: true },
      { key: 'course', labelKey: 'entityFields.course', type: 'number', required: true },
      { key: 'studentCount', labelKey: 'entityFields.studentCount', type: 'number', required: true },
    ],
    columns: [
      { key: 'name', labelKey: 'entityFields.name', format: lookupLabel },
      { key: 'speciality', labelKey: 'entityFields.speciality', format: lookupLabel },
      { key: 'course', labelKey: 'entityFields.course' },
      { key: 'studentCount', labelKey: 'entityFields.students' },
    ],
  },
  {
    key: 'teachers',
    titleKey: 'entities.teachers.title',
    hintKey: 'entities.teachers.hint',
    routeName: 'admin-teachers',
    endpoint: '/api/admin/teachers',
    fields: [
      { key: 'firstName', labelKey: 'entityFields.firstName', type: 'text', required: true },
      { key: 'lastName', labelKey: 'entityFields.lastName', type: 'text', required: true },
      { key: 'department', labelKey: 'entityFields.department', type: 'text', required: true },
    ],
    columns: [
      { key: 'firstName', labelKey: 'entityFields.firstName' },
      { key: 'lastName', labelKey: 'entityFields.lastName' },
      { key: 'department', labelKey: 'entityFields.department' },
    ],
  },
  {
    key: 'subjects',
    titleKey: 'entities.subjects.title',
    hintKey: 'entities.subjects.hint',
    routeName: 'admin-subjects',
    endpoint: '/api/admin/subjects',
    fields: [{ key: 'name', labelKey: 'entityFields.name', type: 'text', required: true }],
    columns: [{ key: 'name', labelKey: 'entityFields.name' }],
  },
  {
    key: 'teacher-subjects',
    titleKey: 'entities.teacherSubjects.title',
    hintKey: 'entities.teacherSubjects.hint',
    routeName: 'admin-teacher-subjects',
    endpoint: '/api/admin/teacher-subjects',
    fields: [
      { key: 'teacherId', labelKey: 'entityFields.teacher', type: 'select', lookup: 'teachers', required: true },
      { key: 'subjectId', labelKey: 'entityFields.subject', type: 'select', lookup: 'subjects', required: true },
    ],
    columns: [
      { key: 'teacherId', labelKey: 'entityFields.teacher' },
      { key: 'subjectId', labelKey: 'entityFields.subject' },
    ],
  },
  {
    key: 'teacher-unavailability',
    titleKey: 'entities.teacherUnavailability.title',
    hintKey: 'entities.teacherUnavailability.hint',
    routeName: 'admin-teacher-unavailability',
    endpoint: '/api/admin/teacher-unavailability',
    fields: [
      { key: 'teacherId', labelKey: 'entityFields.teacher', type: 'select', lookup: 'teachers', required: true },
      {
        key: 'dayOfWeek',
        labelKey: 'entityFields.day',
        type: 'select',
        required: true,
        options: weekdayOptions,
      },
      { key: 'unavailableFrom', labelKey: 'entityFields.unavailableFrom', type: 'time', required: true },
      { key: 'unavailableTo', labelKey: 'entityFields.unavailableTo', type: 'time', required: true },
    ],
    columns: [
      { key: 'teacherId', labelKey: 'entityFields.teacher' },
      { key: 'dayOfWeek', labelKey: 'entityFields.day' },
      { key: 'unavailableFrom', labelKey: 'entityFields.unavailableFrom' },
      { key: 'unavailableTo', labelKey: 'entityFields.unavailableTo' },
    ],
  },
  {
    key: 'rooms',
    titleKey: 'entities.rooms.title',
    hintKey: 'entities.rooms.hint',
    routeName: 'admin-rooms',
    endpoint: '/api/admin/rooms',
    fields: [
      { key: 'name', labelKey: 'entityFields.name', type: 'text', required: true },
      {
        key: 'type',
        labelKey: 'entityFields.type',
        type: 'select',
        required: true,
        options: [
          { value: 'lecture', labelKey: 'roomTypes.lecture' },
          { value: 'computer', labelKey: 'roomTypes.computer' },
        ],
      },
      { key: 'capacity', labelKey: 'entityFields.capacity', type: 'number', required: true },
    ],
    columns: [
      { key: 'name', labelKey: 'entityFields.name' },
      { key: 'type', labelKey: 'entityFields.type' },
      { key: 'capacity', labelKey: 'entityFields.capacity' },
    ],
  },
  {
    key: 'time-slots',
    titleKey: 'entities.timeSlots.title',
    hintKey: 'entities.timeSlots.hint',
    routeName: 'admin-time-slots',
    endpoint: '/api/admin/time-slots',
    fields: [
      { key: 'number', labelKey: 'entityFields.number', type: 'number', required: true },
      { key: 'startsAt', labelKey: 'entityFields.startsAt', type: 'time', required: true },
      { key: 'endsAt', labelKey: 'entityFields.endsAt', type: 'time', required: true },
    ],
    columns: [
      { key: 'number', labelKey: 'entityFields.number' },
      { key: 'startsAt', labelKey: 'entityFields.startsAt' },
      { key: 'endsAt', labelKey: 'entityFields.endsAt' },
    ],
  },
  {
    key: 'academic-years',
    titleKey: 'entities.academicYears.title',
    hintKey: 'entities.academicYears.hint',
    routeName: 'admin-academic-years',
    endpoint: '/api/admin/academic-years',
    fields: [
      { key: 'name', labelKey: 'entityFields.name', type: 'text', required: true },
      { key: 'startsAt', labelKey: 'entityFields.startsAt', type: 'date', required: true },
      { key: 'endsAt', labelKey: 'entityFields.endsAt', type: 'date', required: true },
    ],
    columns: [
      { key: 'name', labelKey: 'entityFields.name' },
      { key: 'startsAt', labelKey: 'entityFields.startsAt' },
      { key: 'endsAt', labelKey: 'entityFields.endsAt' },
    ],
  },
  {
    key: 'semesters',
    titleKey: 'entities.semesters.title',
    hintKey: 'entities.semesters.hint',
    routeName: 'admin-semesters',
    endpoint: '/api/admin/semesters',
    fields: [
      { key: 'academicYearId', labelKey: 'entityFields.academicYear', type: 'select', lookup: 'academicYears', required: true },
      { key: 'number', labelKey: 'entityFields.number', type: 'number', required: true },
      { key: 'startsAt', labelKey: 'entityFields.startsAt', type: 'date', required: true },
      { key: 'endsAt', labelKey: 'entityFields.endsAt', type: 'date', required: true },
      {
        key: 'firstWeekParity',
        labelKey: 'entityFields.firstWeek',
        type: 'select',
        required: true,
        options: [
          { value: 'odd', labelKey: 'weekParityOptions.odd' },
          { value: 'even', labelKey: 'weekParityOptions.even' },
        ],
      },
    ],
    columns: [
      { key: 'academicYearId', labelKey: 'entityFields.academicYear' },
      { key: 'number', labelKey: 'entityFields.number' },
      { key: 'startsAt', labelKey: 'entityFields.startsAt' },
      { key: 'endsAt', labelKey: 'entityFields.endsAt' },
      { key: 'firstWeekParity', labelKey: 'entityFields.firstWeek' },
    ],
  },
  {
    key: 'teaching-loads',
    titleKey: 'entities.teachingLoads.title',
    hintKey: 'entities.teachingLoads.hint',
    routeName: 'admin-teaching-loads',
    endpoint: '/api/admin/teaching-loads',
    fields: [
      { key: 'semesterId', labelKey: 'entityFields.semester', type: 'select', lookup: 'semesters', required: true },
      { key: 'groupId', labelKey: 'entityFields.group', type: 'select', lookup: 'groups', required: true },
      { key: 'subjectId', labelKey: 'entityFields.subject', type: 'select', lookup: 'subjects', required: true },
      { key: 'teacherId', labelKey: 'entityFields.teacher', type: 'select', lookup: 'teachers', required: true },
      {
        key: 'lessonType',
        labelKey: 'entityFields.lessonType',
        type: 'select',
        required: true,
        options: [
          { value: 'lecture', labelKey: 'lessonTypes.lecture' },
          { value: 'laboratory', labelKey: 'lessonTypes.laboratory' },
          { value: 'seminar', labelKey: 'lessonTypes.seminar' },
          { value: 'practical', labelKey: 'lessonTypes.practical' },
        ],
      },
      { key: 'requiredLessonCount', labelKey: 'entityFields.requiredLessonCount', type: 'number', required: true },
      { key: 'requiresComputerRoom', labelKey: 'entityFields.requiresComputerRoom', type: 'boolean' },
    ],
    columns: [
      { key: 'semesterId', labelKey: 'entityFields.semester' },
      { key: 'groupId', labelKey: 'entityFields.group' },
      { key: 'subjectId', labelKey: 'entityFields.subject' },
      { key: 'teacherId', labelKey: 'entityFields.teacher' },
      { key: 'lessonType', labelKey: 'entityFields.type' },
      { key: 'requiredLessonCount', labelKey: 'entityFields.count' },
      { key: 'requiresComputerRoom', labelKey: 'entityFields.computers', format: booleanLabel },
    ],
  },
]

export function entityConfigByKey(key: string): EntityConfig | undefined {
  return entityConfigs.find((config) => config.key === key)
}
