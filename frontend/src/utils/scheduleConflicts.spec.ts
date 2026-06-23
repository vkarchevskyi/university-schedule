import { describe, expect, it } from 'vitest'

import { translateApiValidationMessage, translateScheduleConflict } from '@/utils/scheduleConflicts'

describe('translateScheduleConflict', () => {
  it('translates teacher conflict to Ukrainian', () => {
    expect(
      translateScheduleConflict(
        'teacher_conflict',
        'Teacher is already assigned at this time.',
        'uk',
      ),
    ).toBe('Викладач уже зайнятий у цей час.')
  })

  it('translates room capacity conflict with parameters', () => {
    expect(
      translateScheduleConflict(
        'room_capacity_conflict',
        'Room capacity is 30, but scheduled groups contain 45 students.',
        'uk',
      ),
    ).toBe('Місткість аудиторії — 30 місць, а на занятті 45 студентів.')
  })

  it('translates exam-specific teacher conflict', () => {
    expect(
      translateScheduleConflict(
        'teacher_conflict',
        'Teacher is already assigned at this exam time.',
        'uk',
        'exam',
      ),
    ).toBe('Викладач уже зайнятий у цей час іспиту.')
  })

  it('translates teaching load mismatch variants', () => {
    expect(
      translateScheduleConflict(
        'teaching_load_mismatch',
        'Schedule entry references unknown teaching load 12.',
        'uk',
      ),
    ).toBe('Заняття посилається на невідоме навантаження №12.')
  })
})

describe('translateApiValidationMessage', () => {
  it('translates entry conflict messages', () => {
    expect(
      translateApiValidationMessage('Teacher is already assigned at this time.', 'uk'),
    ).toBe('Викладач уже зайнятий у цей час.')
  })

  it('keeps English messages in English locale', () => {
    expect(
      translateApiValidationMessage('Teacher is already assigned at this time.', 'en'),
    ).toBe('Teacher is already assigned at this time.')
  })
})
