import type { EntityConfig } from '@/types/adminEntities'
import { scheduleWeekdays } from '@/utils/date'

function text(value: unknown): string {
  return value === null || value === undefined ? '' : String(value)
}

function lookupLabel(value: unknown): string {
  return text(value)
}

const weekdayLabels = [
  'Понеділок',
  'Вівторок',
  'Середа',
  'Четвер',
  "П'ятниця",
]

const weekdayOptions = scheduleWeekdays.map((value, index) => ({
  value,
  label: weekdayLabels[index] ?? String(value),
}))

export const entityConfigs: EntityConfig[] = [
  {
    key: 'groups',
    title: 'Групи',
    routeName: 'admin-groups',
    endpoint: '/api/admin/groups',
    fields: [
      { key: 'name', label: 'Назва', type: 'text', required: true },
      { key: 'speciality', label: 'Спеціальність', type: 'text', required: true },
      { key: 'course', label: 'Курс', type: 'number', required: true },
      { key: 'studentCount', label: 'Кількість студентів', type: 'number', required: true },
    ],
    columns: [
      { key: 'name', label: 'Назва', format: lookupLabel },
      { key: 'speciality', label: 'Спеціальність', format: lookupLabel },
      { key: 'course', label: 'Курс' },
      { key: 'studentCount', label: 'Студенти' },
    ],
  },
  {
    key: 'teachers',
    title: 'Викладачі',
    routeName: 'admin-teachers',
    endpoint: '/api/admin/teachers',
    fields: [
      { key: 'firstName', label: "Ім'я", type: 'text', required: true },
      { key: 'lastName', label: 'Прізвище', type: 'text', required: true },
      { key: 'department', label: 'Кафедра', type: 'text', required: true },
    ],
    columns: [
      { key: 'firstName', label: "Ім'я" },
      { key: 'lastName', label: 'Прізвище' },
      { key: 'department', label: 'Кафедра' },
    ],
  },
  {
    key: 'subjects',
    title: 'Предмети',
    routeName: 'admin-subjects',
    endpoint: '/api/admin/subjects',
    fields: [{ key: 'name', label: 'Назва', type: 'text', required: true }],
    columns: [{ key: 'name', label: 'Назва' }],
  },
  {
    key: 'teacher-subjects',
    title: 'Предмети викладачів',
    routeName: 'admin-teacher-subjects',
    endpoint: '/api/admin/teacher-subjects',
    fields: [
      { key: 'teacherId', label: 'Викладач', type: 'select', lookup: 'teachers', required: true },
      { key: 'subjectId', label: 'Предмет', type: 'select', lookup: 'subjects', required: true },
    ],
    columns: [
      { key: 'teacherId', label: 'Викладач' },
      { key: 'subjectId', label: 'Предмет' },
    ],
  },
  {
    key: 'teacher-unavailability',
    title: 'Недоступність викладачів',
    routeName: 'admin-teacher-unavailability',
    endpoint: '/api/admin/teacher-unavailability',
    fields: [
      { key: 'teacherId', label: 'Викладач', type: 'select', lookup: 'teachers', required: true },
      {
        key: 'dayOfWeek',
        label: 'День',
        type: 'select',
        required: true,
        options: weekdayOptions,
      },
      { key: 'unavailableFrom', label: 'Недоступний з', type: 'time', required: true },
      { key: 'unavailableTo', label: 'Недоступний до', type: 'time', required: true },
    ],
    columns: [
      { key: 'teacherId', label: 'Викладач' },
      { key: 'dayOfWeek', label: 'День' },
      { key: 'unavailableFrom', label: 'Недоступний з' },
      { key: 'unavailableTo', label: 'Недоступний до' },
    ],
  },
  {
    key: 'rooms',
    title: 'Аудиторії',
    routeName: 'admin-rooms',
    endpoint: '/api/admin/rooms',
    fields: [
      { key: 'name', label: 'Назва', type: 'text', required: true },
      { key: 'type', label: 'Тип', type: 'text', required: true },
      { key: 'capacity', label: 'Місткість', type: 'number', required: true },
    ],
    columns: [
      { key: 'name', label: 'Назва' },
      { key: 'type', label: 'Тип' },
      { key: 'capacity', label: 'Місткість' },
    ],
  },
  {
    key: 'time-slots',
    title: 'Пари',
    routeName: 'admin-time-slots',
    endpoint: '/api/admin/time-slots',
    fields: [
      { key: 'number', label: 'Номер', type: 'number', required: true },
      { key: 'startsAt', label: 'Початок', type: 'time', required: true },
      { key: 'endsAt', label: 'Кінець', type: 'time', required: true },
    ],
    columns: [
      { key: 'number', label: 'Номер' },
      { key: 'startsAt', label: 'Початок' },
      { key: 'endsAt', label: 'Кінець' },
    ],
  },
  {
    key: 'academic-years',
    title: 'Навчальні роки',
    routeName: 'admin-academic-years',
    endpoint: '/api/admin/academic-years',
    fields: [
      { key: 'name', label: 'Назва', type: 'text', required: true },
      { key: 'startsAt', label: 'Початок', type: 'date', required: true },
      { key: 'endsAt', label: 'Кінець', type: 'date', required: true },
    ],
    columns: [
      { key: 'name', label: 'Назва' },
      { key: 'startsAt', label: 'Початок' },
      { key: 'endsAt', label: 'Кінець' },
    ],
  },
  {
    key: 'semesters',
    title: 'Семестри',
    routeName: 'admin-semesters',
    endpoint: '/api/admin/semesters',
    fields: [
      { key: 'academicYearId', label: 'Навчальний рік', type: 'select', lookup: 'academicYears', required: true },
      { key: 'number', label: 'Номер', type: 'number', required: true },
      { key: 'startsAt', label: 'Початок', type: 'date', required: true },
      { key: 'endsAt', label: 'Кінець', type: 'date', required: true },
      {
        key: 'firstWeekParity',
        label: 'Перший тиждень',
        type: 'select',
        required: true,
        options: [
          { value: 'odd', label: 'Непарний' },
          { value: 'even', label: 'Парний' },
        ],
      },
    ],
    columns: [
      { key: 'academicYearId', label: 'Навчальний рік' },
      { key: 'number', label: 'Номер' },
      { key: 'startsAt', label: 'Початок' },
      { key: 'endsAt', label: 'Кінець' },
      { key: 'firstWeekParity', label: 'Перший тиждень' },
    ],
  },
  {
    key: 'teaching-loads',
    title: 'Навчальне навантаження',
    routeName: 'admin-teaching-loads',
    endpoint: '/api/admin/teaching-loads',
    fields: [
      { key: 'semesterId', label: 'Семестр', type: 'select', lookup: 'semesters', required: true },
      { key: 'groupId', label: 'Група', type: 'select', lookup: 'groups', required: true },
      { key: 'subjectId', label: 'Предмет', type: 'select', lookup: 'subjects', required: true },
      { key: 'teacherId', label: 'Викладач', type: 'select', lookup: 'teachers', required: true },
      {
        key: 'lessonType',
        label: 'Тип заняття',
        type: 'select',
        required: true,
        options: [
          { value: 'lecture', label: 'Лекція' },
          { value: 'laboratory', label: 'Лабораторна' },
          { value: 'seminar', label: 'Семінар' },
          { value: 'practical', label: 'Практична' },
        ],
      },
      { key: 'requiredLessonCount', label: 'Кількість занять', type: 'number', required: true },
    ],
    columns: [
      { key: 'semesterId', label: 'Семестр' },
      { key: 'groupId', label: 'Група' },
      { key: 'subjectId', label: 'Предмет' },
      { key: 'teacherId', label: 'Викладач' },
      { key: 'lessonType', label: 'Тип' },
      { key: 'requiredLessonCount', label: 'Кількість' },
    ],
  },
]

export function entityConfigByKey(key: string): EntityConfig | undefined {
  return entityConfigs.find((config) => config.key === key)
}
