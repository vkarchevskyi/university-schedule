import type { AdminLocale } from '@/i18n/admin'

type ConflictTemplate = string | ((...values: number[]) => string)

const scheduleConflictTemplates: Record<AdminLocale, Record<string, ConflictTemplate>> = {
  uk: {
    teacher_conflict: 'Викладач уже зайнятий у цей час.',
    room_conflict: 'Аудиторія уже зайнята у цей час.',
    group_conflict: 'Група уже має заняття у цей час.',
    room_capacity_conflict: (capacity, students) =>
      `Місткість аудиторії — ${capacity} місць, а на занятті ${students} студентів.`,
    room_type_conflict: (teachingLoadId) =>
      `Навчальне навантаження №${teachingLoadId} потребує комп'ютерної аудиторії.`,
    teacher_subject_mismatch: 'Викладач не призначений до цього предмета.',
    invalid_time_slot: 'Некоректний часовий інтервал заняття.',
    invalid_teacher_unavailability: 'Некоректний інтервал недоступності викладача.',
    teacher_unavailability_conflict: 'Викладач недоступний у цей час.',
    invalid_day_of_week: 'Заняття можна ставити лише з понеділка по пʼятницю.',
    invalid_schedule_period: 'Некоректний період дії розкладу.',
    schedule_period_outside_semester: 'Період дії розкладу має бути в межах семестру.',
    teaching_load_missing: (teachingLoadId, required, scheduled) =>
      `Навчальне навантаження №${teachingLoadId} потребує ${required} занять, а заплановано лише ${scheduled}.`,
    teaching_load_overscheduled: (teachingLoadId, required, scheduled) =>
      `Навчальне навантаження №${teachingLoadId} потребує ${required} занять, а заплановано ${scheduled}.`,
    consultation_missing: (days) => `Для іспиту потрібна консультація за ${days} дн. до іспиту.`,
    group_exam_interval_conflict: (days) =>
      `Іспити однієї групи мають бути щонайменше через ${days} дн.`,
  },
  en: {
    teacher_conflict: 'Teacher is already assigned at this time.',
    room_conflict: 'Room is already assigned at this time.',
    group_conflict: 'Group is already assigned at this time.',
    room_capacity_conflict: (capacity, students) =>
      `Room capacity is ${capacity}, but scheduled groups contain ${students} students.`,
    room_type_conflict: (teachingLoadId) => `Teaching load ${teachingLoadId} requires a computer room.`,
    teacher_subject_mismatch: 'Teacher is not assigned to this subject.',
    invalid_time_slot: 'Schedule entry has an invalid time slot range.',
    invalid_teacher_unavailability: 'Teacher unavailability has an invalid time range.',
    teacher_unavailability_conflict: 'Teacher is unavailable at this time.',
    invalid_day_of_week: 'Schedule entries can only be placed Monday through Friday.',
    invalid_schedule_period: 'Schedule has an invalid date range.',
    schedule_period_outside_semester: 'Schedule validity period must be within the semester.',
    teaching_load_missing: (teachingLoadId, required, scheduled) =>
      `Teaching load ${teachingLoadId} requires ${required} lessons, but only ${scheduled} are scheduled.`,
    teaching_load_overscheduled: (teachingLoadId, required, scheduled) =>
      `Teaching load ${teachingLoadId} requires ${required} lessons, but ${scheduled} are scheduled.`,
    consultation_missing: (days) => `Exam requires a matching consultation ${days} day(s) before the exam.`,
    group_exam_interval_conflict: (days) => `Group exams must be at least ${days} day(s) apart.`,
  },
}

const examConflictOverridesUk: Partial<Record<string, ConflictTemplate>> = {
  teacher_conflict: 'Викладач уже зайнятий у цей час іспиту.',
  room_conflict: 'Аудиторія уже зайнята у цей час іспиту.',
  group_conflict: 'Група уже має іспит у цей час.',
  room_capacity_conflict: (capacity, students) =>
    `Місткість аудиторії — ${capacity} місць, а на іспиті ${students} студентів.`,
}

const examConflictOverridesEn: Partial<Record<string, ConflictTemplate>> = {
  teacher_conflict: 'Teacher is already assigned at this exam time.',
  room_conflict: 'Room is already assigned at this exam time.',
  group_conflict: 'Group is already assigned at this exam time.',
  room_capacity_conflict: (capacity, students) =>
    `Room capacity is ${capacity}, but exam groups contain ${students} students.`,
}

const apiValidationMessagesUk: Record<string, string> = {
  'Teacher is already assigned at this time.': 'Викладач уже зайнятий у цей час.',
  'Room is already assigned at this time.': 'Аудиторія уже зайнята у цей час.',
  'Group is already assigned at this time.': 'Група уже має заняття у цей час.',
  'Only draft schedules can be edited.': 'Редагувати можна лише чернетки розкладу.',
  'Expected schedule entry data.': 'Очікувались дані заняття.',
  'Expected unique identifiers.': 'Очікувались унікальні ідентифікатори.',
  'Schedule must belong to a semester.': 'Розклад має належати семестру.',
  'Deleted teaching loads cannot be scheduled.': 'Видалене навантаження не можна планувати.',
  'Teaching load must belong to the schedule semester.': 'Навчальне навантаження має належати семестру розкладу.',
  'Teaching load must match subject, teacher, and lesson type.':
    'Навчальне навантаження має відповідати предмету, викладачу та типу заняття.',
  'Teaching load must match the entry subgroup.': 'Навчальне навантаження має відповідати підгрупі заняття.',
  'Teaching load requires a computer room.': 'Навчальне навантаження потребує компʼютерної аудиторії.',
  'Schedule entry groups must include each teaching load group.':
    'Групи заняття мають охоплювати кожну групу з навантаження.',
  'Only draft schedules can be published.': 'Опублікувати можна лише чернетку розкладу.',
  'Teacher is not assigned to this subject.': 'Викладач не призначений до цього предмета.',
}

function extractConflictParams(type: string, message: string): number[] | null {
  switch (type) {
    case 'room_capacity_conflict': {
      const match = message.match(
        /Room capacity is (\d+), but (?:scheduled groups contain|exam groups contain) (\d+) students?/,
      )
      return match ? [Number(match[1]), Number(match[2])] : null
    }
    case 'room_type_conflict': {
      const match = message.match(/Teaching load (\d+) requires a computer room/)
      return match ? [Number(match[1])] : null
    }
    case 'teaching_load_missing':
    case 'teaching_load_overscheduled': {
      const match = message.match(
        /Teaching load (\d+) requires (\d+) lessons, but (?:only )?(\d+) are scheduled/,
      )
      return match ? [Number(match[1]), Number(match[2]), Number(match[3])] : null
    }
    case 'consultation_missing': {
      const match = message.match(/Exam requires a matching consultation (\d+) day\(s\) before the exam/)
      return match ? [Number(match[1])] : null
    }
    case 'group_exam_interval_conflict': {
      const match = message.match(/Group exams must be at least (\d+) day\(s\) apart/)
      return match ? [Number(match[1])] : null
    }
    default:
      return null
  }
}

function isExamConflictMessage(message: string): boolean {
  return message.includes('exam')
}

function resolveTeachingLoadMismatch(type: string, message: string, locale: AdminLocale): string | null {
  if (type !== 'teaching_load_mismatch') {
    return null
  }

  const unknownMatch = message.match(/Schedule entry references unknown teaching load (\d+)/)
  if (unknownMatch) {
    const id = unknownMatch[1]
    return locale === 'uk'
      ? `Заняття посилається на невідоме навантаження №${id}.`
      : `Schedule entry references unknown teaching load ${id}.`
  }

  const mismatchMatch = message.match(/Schedule entry does not match teaching load (\d+)/)
  if (mismatchMatch) {
    const id = mismatchMatch[1]
    return locale === 'uk'
      ? `Заняття не відповідає навантаженню №${id}.`
      : `Schedule entry does not match teaching load ${id}.`
  }

  return null
}

function applyTemplate(template: ConflictTemplate, params: number[] | null, fallback: string): string {
  if (typeof template === 'string') {
    return template
  }

  if (params === null) {
    return fallback
  }

  return template(...params)
}

export function translateScheduleConflict(
  type: string,
  message: string,
  locale: AdminLocale,
  context: 'schedule' | 'exam' = 'schedule',
): string {
  const teachingLoadMismatch = resolveTeachingLoadMismatch(type, message, locale)
  if (teachingLoadMismatch !== null) {
    return teachingLoadMismatch
  }

  const templates = scheduleConflictTemplates[locale]
  let template = templates[type]

  if (context === 'exam' && locale === 'uk' && isExamConflictMessage(message)) {
    template = examConflictOverridesUk[type] ?? template
  } else if (context === 'exam' && locale === 'en' && isExamConflictMessage(message)) {
    template = examConflictOverridesEn[type] ?? template
  }

  if (template === undefined) {
    return message
  }

  return applyTemplate(template, extractConflictParams(type, message), message)
}

export function translateApiValidationMessage(message: string, locale: AdminLocale): string {
  if (locale === 'en') {
    return message
  }

  return apiValidationMessagesUk[message] ?? message
}
