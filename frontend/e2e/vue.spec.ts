import { test, expect } from '@playwright/test'
import type { Page } from '@playwright/test'

test.beforeEach(async ({ page }) => {
  await page.route('**/api/public/groups', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        items: [
          { id: 1, name: 'КН-22', speciality: "Комп'ютерні науки", course: 4, studentCount: 24 },
        ],
      }),
    })
  })

  await page.route('**/api/public/teachers', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        items: [{ id: 7, firstName: 'Іван', lastName: 'Петренко', department: 'Інформатика' }],
      }),
    })
  })

  await page.route('**/api/public/rooms', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        items: [{ id: 3, name: 'Лаб 1', type: 'computer', capacity: 30 }],
      }),
    })
  })
})

test('loads the default group schedule', async ({ page }) => {
  await mockSchedule(page)

  await page.goto('/')

  await expect(page.getByRole('heading', { name: 'Розклад занять' })).toBeVisible()
  const desktopSchedule = page.getByTestId('desktop-schedule')
  await expect(desktopSchedule).toBeVisible()
  await expect(desktopSchedule.getByTestId('schedule-card')).toContainText('Алгоритми')
  await expect(desktopSchedule.getByTestId('schedule-card')).toContainText('КН-22')
})

test('switches schedule type and reloads with correct query params', async ({ page }) => {
  let lastUrl = ''
  await mockSchedule(page, (url) => {
    lastUrl = url.href
  })

  await page.goto('/')
  await page.getByRole('button', { name: 'Викладач' }).click()

  await expect.poll(() => lastUrl).toContain('type=teacher')
  expect(lastUrl).toContain('id=7')
})

test('navigates weeks', async ({ page }) => {
  const requestedWeekStarts: string[] = []
  await mockSchedule(page, (url) => {
    requestedWeekStarts.push(url.searchParams.get('weekStart') ?? '')
  })

  await page.goto('/')
  await page.getByTestId('next-week').click()
  await page.getByTestId('previous-week').click()

  await expect.poll(() => requestedWeekStarts.length).toBeGreaterThanOrEqual(3)
  expect(new Set(requestedWeekStarts).size).toBeGreaterThan(1)
})

test('renders empty schedule state', async ({ page }) => {
  await mockSchedule(page, undefined, [])

  await page.goto('/')

  await expect(page.getByTestId('empty-state')).toContainText(
    'Для вибраного тижня немає опублікованих занять.',
  )
})

test('renders error state and retries', async ({ page }) => {
  let calls = 0
  await page.route('**/api/public/schedule?**', async (route) => {
    calls += 1
    if (calls === 1) {
      await route.fulfill({ status: 500, body: 'error' })
      return
    }
    const url = new URL(route.request().url())
    const weekStart = url.searchParams.get('weekStart') ?? '2026-09-07'

    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(scheduleResponse(itemsForWeek(weekStart, scheduleItems), weekStart)),
    })
  })

  await page.goto('/')
  await expect(page.getByTestId('error-state')).toContainText('Не вдалося завантажити розклад.')

  await page.getByTestId('retry-button').click()

  await expect(page.getByTestId('desktop-schedule').getByTestId('schedule-card')).toContainText('Алгоритми')
})

test('uses the mobile day list layout on small screens', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await mockSchedule(page)

  await page.goto('/')

  await expect(page.getByTestId('mobile-schedule')).toBeVisible()
  await expect(page.getByTestId('desktop-schedule')).toBeHidden()
})

test('redirects anonymous admin users to login', async ({ page }) => {
  await mockSchedule(page)

  await page.goto('/admin')

  await expect(page).toHaveURL(/\/admin\/login$/)
  await expect(page.getByRole('heading', { name: 'Вхід адміністратора' })).toBeVisible()
})

test('logs in and shows the admin dashboard', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)

  await page.goto('/admin/login')
  await page.getByLabel('Email').fill('admin@example.com')
  await page.getByLabel('Пароль').fill('correct-password')
  await page.getByTestId('login-submit').click()

  await expect(page).toHaveURL(/\/admin$/)
  await expect(page.getByTestId('admin-dashboard')).toContainText('Панель адміністратора')
  await expect(page.getByTestId('admin-name')).toHaveText('Ada Lovelace')
})

test('shows an error for invalid admin login', async ({ page }) => {
  await mockSchedule(page)
  await page.route('**/api/auth/login', async (route) => {
    await route.fulfill({ status: 401, body: 'invalid' })
  })

  await page.goto('/admin/login')
  await page.getByLabel('Email').fill('admin@example.com')
  await page.getByLabel('Пароль').fill('wrong-password')
  await page.getByTestId('login-submit').click()

  await expect(page).toHaveURL(/\/admin\/login$/)
  await expect(page.getByTestId('login-error')).toContainText('Невірний email або пароль.')
})

test('logs out and clears the admin session', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)

  await page.goto('/admin/login')
  await page.getByLabel('Email').fill('admin@example.com')
  await page.getByLabel('Пароль').fill('correct-password')
  await page.getByTestId('login-submit').click()
  await page.getByTestId('logout-button').click()

  await expect(page).toHaveURL(/\/admin\/login$/)
  await expect(page.evaluate(() => window.localStorage.getItem('university-schedule.user-token'))).resolves.toBeNull()
})

test('loads persisted admin token on protected routes', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin')

  await expect(page.getByTestId('admin-dashboard')).toBeVisible()
  await expect(page.getByTestId('admin-name')).toHaveText('Ada Lovelace')
})

test('opens schedule management and creates a draft schedule', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await mockAdminScheduleManagement(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin/schedules')

  await expect(page.getByRole('heading', { name: 'Розклади' })).toBeVisible()
  await expect(page.getByTestId('schedule-list')).toContainText('#12')

  await page.getByTestId('create-schedule').click()

  await expect(page).toHaveURL(/\/admin\/schedules\/13$/)
  await expect(page.getByRole('heading', { name: 'Редактор розкладу #13' })).toBeVisible()
})

test('places, edits, validates, and deletes a schedule entry', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await mockAdminScheduleManagement(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin/schedules/12')

  await expect(page.getByTestId('lesson-card')).toContainText('Алгоритми')

  await page.evaluate(() => {
    const card = document.querySelector('[data-testid="lesson-card"]')
    const cell = document.querySelector('[data-testid="schedule-cell"]')
    if (!(card instanceof HTMLElement) || !(cell instanceof HTMLElement)) {
      throw new Error('Schedule editor test elements are missing')
    }

    const dataTransfer = new DataTransfer()
    card.dispatchEvent(new DragEvent('dragstart', { bubbles: true, dataTransfer }))
    cell.dispatchEvent(new DragEvent('drop', { bubbles: true, dataTransfer }))
  })

  await expect(page.getByTestId('schedule-entry')).toContainText('Лекція')

  await page.getByTestId('schedule-entry').click()
  await page.getByTestId('week-parity-select').selectOption('odd')
  await page.getByTestId('save-entry').click()

  await expect(page.getByTestId('schedule-entry')).toContainText('Непарний')

  await page.getByTestId('validate-schedule').click()

  await expect(page.getByTestId('validation-result')).toContainText('Потрібно вибрати іншу аудиторію.')

  await page.getByTestId('schedule-entry').click()
  await page.getByTestId('delete-entry').click()

  await expect(page.getByTestId('schedule-entry')).toHaveCount(0)
})

test('creates a group through the shared admin CRUD page', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await mockAdminEntityManagement(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin/entities/groups')

  await expect(page.getByRole('heading', { name: 'Групи' })).toBeVisible()
  await expect(page.getByTestId('entity-table')).toContainText('КН-22')

  await page.getByTestId('create-entity').click()
  await page.getByLabel('Назва').fill('КН-23')
  await page.getByLabel('Спеціальність').fill("Комп'ютерні науки")
  await page.getByLabel('Курс').fill('3')
  await page.getByLabel('Кількість студентів').fill('21')
  await page.getByRole('button', { name: 'Зберегти' }).click()

  await expect(page.getByTestId('entity-table')).toContainText('КН-23')
})

test('generates a draft schedule and opens the generated result', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await mockAdminScheduleManagement(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin/schedules')
  await page.getByTestId('generate-schedule').click()

  await expect(page.getByTestId('generation-job')).toContainText('completed')
  await page.getByTestId('open-generated-schedule').click()

  await expect(page).toHaveURL(/\/admin\/schedules\/14$/)
})

test('creates, validates, edits, and deletes an exam schedule entry', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await mockAdminScheduleManagement(page)
  await mockAdminExamScheduleManagement(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin/exam-schedules/21')

  await page.getByLabel('Дата').fill('2026-12-20')
  await page.getByLabel('Початок').fill('10:00')
  await page.getByTestId('save-exam-entry').click()

  await expect(page.getByTestId('exam-entry-table')).toContainText('2026-12-20')

  await page.getByTestId('edit-exam-entry').click()
  await page.getByLabel('Початок').fill('11:00')
  await page.getByTestId('save-exam-entry').click()

  await expect(page.getByTestId('exam-entry-table')).toContainText('11:00')

  await page.getByTestId('validate-exam-schedule').click()
  await expect(page.getByTestId('exam-validation-result')).toContainText('Розклад іспитів валідний.')

  await page.getByTestId('delete-exam-entry').click()
  await expect(page.getByTestId('exam-entry-table')).not.toContainText('2026-12-20')
})

test('generates an exam draft and opens the generated result', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await mockAdminScheduleManagement(page)
  await mockAdminExamScheduleManagement(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')
  })

  await page.goto('/admin/exam-schedules')
  await page.getByTestId('generate-exam-schedule').click()

  await expect(page.getByTestId('exam-generation-job')).toContainText('completed')
  await page.getByTestId('open-generated-exam-schedule').click()

  await expect(page).toHaveURL(/\/admin\/exam-schedules\/22$/)
})

async function mockSchedule(
  page: Page,
  onRequest?: (url: URL) => void,
  items = scheduleItems,
): Promise<void> {
  await page.route('**/api/public/schedule?**', async (route) => {
    const url = new URL(route.request().url())
    onRequest?.(url)
    const weekStart = url.searchParams.get('weekStart') ?? '2026-09-07'

    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(scheduleResponse(itemsForWeek(weekStart, items), weekStart)),
    })
  })
}

async function mockSuccessfulAuth(page: Page): Promise<void> {
  await page.route('**/api/auth/login', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        token: 'jwt-token',
        user: userResponse,
      }),
    })
  })

  await page.route('**/api/auth/me', async (route) => {
    const authorization = route.request().headers().authorization ?? ''
    if (authorization !== 'Bearer jwt-token') {
      await route.fulfill({ status: 401, body: 'unauthorized' })
      return
    }

    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ user: userResponse }),
    })
  })
}

async function mockAdminScheduleManagement(page: Page): Promise<void> {
  let entries: AdminScheduleEntry[] = []

  await page.route('**/api/admin/semesters', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminSemester] }),
    })
  })

  await page.route('**/api/admin/rooms', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminRoom] }),
    })
  })

  await page.route('**/api/admin/groups', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminGroup] }),
    })
  })

  await page.route('**/api/admin/teachers', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminTeacher] }),
    })
  })

  await page.route('**/api/admin/subjects', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminSubject] }),
    })
  })

  await page.route('**/api/admin/time-slots', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminTimeSlot] }),
    })
  })

  await page.route('**/api/admin/schedules', async (route) => {
    if (route.request().method() === 'POST') {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify(adminSchedule(13, [])),
      })
      return
    }

    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminSchedule(12, entries)] }),
    })
  })

  await page.route('**/api/admin/schedules?**', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [adminSchedule(12, entries)] }),
    })
  })

  await page.route(/\/api\/admin\/schedules\/\d+\/lesson-cards$/, async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [lessonCard] }),
    })
  })

  await page.route(/\/api\/admin\/schedules\/\d+\/entries$/, async (route) => {
    const payload = (await route.request().postDataJSON()) as AdminScheduleEntryPayload
    entries = [{ id: 77, scheduleId: 12, ...payload }]
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(entries[0]),
    })
  })

  await page.route(/\/api\/admin\/schedules\/\d+\/entries\/\d+$/, async (route) => {
    if (route.request().method() === 'DELETE') {
      entries = []
      await route.fulfill({ status: 204 })
      return
    }

    const payload = (await route.request().postDataJSON()) as Partial<AdminScheduleEntryPayload>
    entries = entries.map((entry) => ({ ...entry, ...payload }))
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(entries[0]),
    })
  })

  await page.route(/\/api\/admin\/schedules\/\d+\/validate$/, async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        valid: false,
        conflicts: [{ type: 'room_conflict', message: 'Потрібно вибрати іншу аудиторію.', entryIds: [77] }],
      }),
    })
  })

  await page.route('**/api/admin/schedules/generate', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(scheduleGenerationJob('running')),
    })
  })

  await page.route('**/api/admin/generation-jobs/schedule-job-1', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(scheduleGenerationJob('completed')),
    })
  })

  await page.route(/\/api\/admin\/schedules\/\d+$/, async (route) => {
    const id = Number(new URL(route.request().url()).pathname.split('/').pop())
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(adminSchedule(id, entries)),
    })
  })
}

async function mockAdminEntityManagement(page: Page): Promise<void> {
  let groups = [adminGroup]

  await page.route('**/api/admin/groups', async (route) => {
    if (route.request().method() === 'POST') {
      const payload = (await route.request().postDataJSON()) as typeof adminGroup
      groups = [...groups, { id: 2, ...payload }]
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify(groups[1]),
      })
      return
    }

    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: groups }),
    })
  })
}

async function mockAdminExamScheduleManagement(page: Page): Promise<void> {
  let entries: ExamEntry[] = []

  await page.route('**/api/admin/exam-schedules', async (route) => {
    if (route.request().method() === 'POST') {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify(examSchedule(22, entries)),
      })
      return
    }

    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [examSchedule(21, entries)] }),
    })
  })

  await page.route('**/api/admin/exam-schedules?**', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ items: [examSchedule(21, entries)] }),
    })
  })

  await page.route(/\/api\/admin\/exam-schedules\/\d+\/entries$/, async (route) => {
    const payload = (await route.request().postDataJSON()) as ExamEntryPayload
    entries = [{ id: 88, scheduleId: 21, ...payload }]
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(entries[0]),
    })
  })

  await page.route(/\/api\/admin\/exam-schedules\/\d+\/entries\/\d+$/, async (route) => {
    if (route.request().method() === 'DELETE') {
      entries = []
      await route.fulfill({ status: 204 })
      return
    }

    const payload = (await route.request().postDataJSON()) as Partial<ExamEntryPayload>
    entries = entries.map((entry) => ({ ...entry, ...payload }))
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(entries[0]),
    })
  })

  await page.route(/\/api\/admin\/exam-schedules\/\d+\/validate$/, async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ valid: true, conflicts: [] }),
    })
  })

  await page.route('**/api/admin/exam-schedules/generate', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(examGenerationJob('running')),
    })
  })

  await page.route('**/api/admin/exam-schedule-generation-jobs/exam-job-1', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(examGenerationJob('completed')),
    })
  })

  await page.route(/\/api\/admin\/exam-schedules\/\d+$/, async (route) => {
    const id = Number(new URL(route.request().url()).pathname.split('/').pop())
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify(examSchedule(id, entries)),
    })
  })
}

function scheduleResponse(items = scheduleItems, weekStart = '2026-09-07') {
  return {
    weekStart,
    type: 'group',
    id: 1,
    items,
  }
}

function itemsForWeek(weekStart: string, items = scheduleItems) {
  return items.map((item) => ({
    ...item,
    date: weekStart,
  }))
}

const scheduleItems = [
  {
    id: 123,
    date: '2026-09-07',
    dayOfWeek: 1,
    lessonType: 'lecture',
    timeSlot: { id: 1, number: 1, startsAt: '08:30:00', endsAt: '10:00:00' },
    subject: { id: 4, name: 'Алгоритми' },
    teacher: { id: 7, firstName: 'Іван', lastName: 'Петренко' },
    room: { id: 3, name: 'Лаб 1', type: 'computer' },
    groups: [{ id: 1, name: 'КН-22' }],
    isCancelled: false,
    isOverride: false,
  },
]

const userResponse = {
  id: 1,
  firstName: 'Ada',
  lastName: 'Lovelace',
  email: 'admin@example.com',
  role: 'admin',
}

interface AdminScheduleEntryPayload {
  teachingLoadIds: number[]
  subjectId: number
  teacherId: number
  lessonType: 'lecture' | 'laboratory' | 'seminar' | 'practical'
  roomId: number
  timeSlotId: number
  dayOfWeek: number
  weekParity: 'odd' | 'even' | 'both'
  groupIds: number[]
}

interface AdminScheduleEntry extends AdminScheduleEntryPayload {
  id: number
  scheduleId: number
}

interface ExamEntryPayload {
  type: 'consultation' | 'exam'
  subjectId: number
  teacherId: number
  roomId: number
  groupIds: number[]
  entryDate: string
  startsAt: string
}

interface ExamEntry extends ExamEntryPayload {
  id: number
  scheduleId: number
}

function adminSchedule(id: number, entries: AdminScheduleEntry[]) {
  return {
    id,
    semesterId: 1,
    status: 'draft',
    validFrom: '2026-09-01',
    validTo: '2026-12-31',
    createdBy: 1,
    createdAt: '2026-05-14T10:00:00+00:00',
    publishedAt: null,
    entries,
  }
}

const adminSemester = {
  id: 1,
  academicYearId: 1,
  number: 1,
  startsAt: '2026-09-01',
  endsAt: '2026-12-31',
  firstWeekParity: 'odd',
}

const adminRoom = {
  id: 3,
  name: 'Лаб 1',
  type: 'computer',
  capacity: 30,
}

const adminGroup = {
  id: 1,
  name: 'КН-22',
  speciality: "Комп'ютерні науки",
  course: 4,
  studentCount: 24,
}

const adminTeacher = {
  id: 7,
  firstName: 'Іван',
  lastName: 'Петренко',
  department: 'Інформатика',
}

const adminSubject = {
  id: 4,
  name: 'Алгоритми',
}

const adminTimeSlot = {
  id: 1,
  number: 1,
  startsAt: '08:30:00',
  endsAt: '10:00:00',
}

function scheduleGenerationJob(status: 'running' | 'completed') {
  return {
    id: 'schedule-job-1',
    status,
    qualityScore: status === 'completed' ? 86 : null,
    diagnostics: status === 'completed' ? ['Чернетку створено.'] : [],
    errorMessage: null,
    generatedScheduleId: status === 'completed' ? 14 : null,
  }
}

function examGenerationJob(status: 'running' | 'completed') {
  return {
    id: 'exam-job-1',
    status,
    qualityScore: status === 'completed' ? 91 : null,
    diagnostics: status === 'completed' ? ['Іспитову чернетку створено.'] : [],
    errorMessage: null,
    generatedExamScheduleId: status === 'completed' ? 22 : null,
  }
}

function examSchedule(id: number, entries: ExamEntry[]) {
  return {
    id,
    semesterId: 1,
    status: 'draft',
    createdAt: '2026-05-14T10:00:00+00:00',
    entries,
  }
}

const lessonCard = {
  teachingLoadId: 44,
  group: { id: 1, name: 'КН-22' },
  subject: { id: 4, name: 'Алгоритми' },
  teacher: { id: 7, firstName: 'Іван', lastName: 'Петренко', department: 'Інформатика' },
  lessonType: 'lecture',
  requiredLessonCount: 8,
  scheduledLessonCount: 0,
  remainingLessonCount: 8,
}
