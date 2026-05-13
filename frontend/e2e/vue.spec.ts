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
  await expect(page.evaluate(() => window.localStorage.getItem('university-schedule.admin-token'))).resolves.toBeNull()
})

test('loads persisted admin token on protected routes', async ({ page }) => {
  await mockSchedule(page)
  await mockSuccessfulAuth(page)
  await page.addInitScript(() => {
    window.localStorage.setItem('university-schedule.admin-token', 'jwt-token')
  })

  await page.goto('/admin')

  await expect(page.getByTestId('admin-dashboard')).toBeVisible()
  await expect(page.getByTestId('admin-name')).toHaveText('Ada Lovelace')
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
        admin: adminResponse,
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
      body: JSON.stringify({ admin: adminResponse }),
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

const adminResponse = {
  id: 1,
  firstName: 'Ada',
  lastName: 'Lovelace',
  email: 'admin@example.com',
}
