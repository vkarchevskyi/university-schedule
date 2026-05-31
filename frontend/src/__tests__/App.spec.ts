import { afterEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { createScheduleEntry, deleteScheduleEntry } from '@/api/adminSchedule'
import { ApiError, requestJson } from '@/api/http'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import PublicSchedulePage from '@/components/pages/PublicSchedulePage.vue'
import { entityConfigByKey } from '@/config/adminEntities'
import { useAuthStore } from '@/stores/auth'
import { useLocaleStore } from '@/stores/locale'
import { addWeeks, currentWeekStart, mondayOfWeek, toIsoDate } from '@/utils/date'

describe('App', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    window.localStorage.clear()
  })

  it('renders the public schedule page', async () => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', mockFetch())

    const wrapper = mount(PublicSchedulePage)
    await flushPromises()

    expect(wrapper.text()).toContain('Розклад занять')
    expect(wrapper.text()).toContain('Алгоритми')
    expect(wrapper.findAll('[data-testid="schedule-card"]').length).toBeGreaterThanOrEqual(1)
  })

  it('computes monday based week navigation', () => {
    expect(toIsoDate(mondayOfWeek(new Date('2026-09-09T12:00:00Z')))).toBe('2026-09-07')
    expect(addWeeks('2026-09-07', 1)).toBe('2026-09-14')
  })

  it('stores authenticated admin session and clears it on logout', async () => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', mockAuthFetch())

    const auth = useAuthStore()
    await auth.login('admin@example.com', 'correct-password')

    expect(auth.token).toBe('jwt-token')
    expect(auth.user?.email).toBe('admin@example.com')
    expect(auth.user?.role).toBe('admin')
    expect(window.localStorage.getItem('university-schedule.user-token')).toBe('jwt-token')

    auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(window.localStorage.getItem('university-schedule.user-token')).toBeNull()
  })

  it('sends bearer token for authenticated requests', async () => {
    let authorizationHeader: string | null = null
    const fetchMock = vi.fn(async (_input: RequestInfo | URL, options?: RequestInit) => {
      const headers = options?.headers
      authorizationHeader = headers instanceof Headers ? headers.get('Authorization') : null

      return jsonResponse({ user: userResponse })
    })
    vi.stubGlobal('fetch', fetchMock)
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')

    await requestJson('/api/auth/me', { authenticated: true })

    expect(authorizationHeader).toBe('Bearer jwt-token')
  })

  it('sends schedule entry mutations through authenticated API requests', async () => {
    const requests: Array<{
      url: string
      method: string
      body: string | null
      authorization: string | null
    }> = []
    vi.stubGlobal(
      'fetch',
      vi.fn(async (input: RequestInfo | URL, options?: RequestInit) => {
        const headers = options?.headers
        requests.push({
          url: String(input),
          method: options?.method ?? 'GET',
          body: typeof options?.body === 'string' ? options.body : null,
          authorization: headers instanceof Headers ? headers.get('Authorization') : null,
        })

        return options?.method === 'DELETE'
          ? new Response(null, { status: 204 })
          : jsonResponse({ id: 99 })
      }),
    )
    window.localStorage.setItem('university-schedule.user-token', 'jwt-token')

    await createScheduleEntry(12, {
      teachingLoadIds: [44],
      subjectId: 3,
      teacherId: 7,
      lessonType: 'lecture',
      roomId: 2,
      timeSlotId: 1,
      dayOfWeek: 1,
      weekParity: 'both',
      groupIds: [9],
    })
    await deleteScheduleEntry(12, 99)

    const createRequest = requests[0]
    const deleteRequest = requests[1]
    expect(createRequest).toBeDefined()
    expect(deleteRequest).toBeDefined()

    if (createRequest === undefined || deleteRequest === undefined) {
      throw new Error('Expected schedule entry mutation requests')
    }

    expect(createRequest).toMatchObject({
      url: 'http://localhost:8000/api/admin/schedules/12/entries',
      method: 'POST',
      authorization: 'Bearer jwt-token',
    })
    expect(JSON.parse(createRequest.body ?? '{}')).toMatchObject({
      teachingLoadIds: [44],
      roomId: 2,
      weekParity: 'both',
    })
    expect(deleteRequest).toMatchObject({
      url: 'http://localhost:8000/api/admin/schedules/12/entries/99',
      method: 'DELETE',
      authorization: 'Bearer jwt-token',
    })
  })

  it('switches locale and persists the selected language', () => {
    setActivePinia(createPinia())

    const locale = useLocaleStore()
    expect(locale.locale).toBe('uk')

    locale.setLocale('en')

    expect(locale.locale).toBe('en')
    expect(locale.languageLabel).toBe('EN')
    expect(window.localStorage.getItem('university-schedule.locale')).toBe('en')
  })

  it('exposes teacher subject and unavailability admin entity configs', () => {
    const teacherSubjects = entityConfigByKey('teacher-subjects')
    const teacherUnavailability = entityConfigByKey('teacher-unavailability')

    expect(teacherSubjects).toMatchObject({
      endpoint: '/api/admin/teacher-subjects',
      fields: [
        { key: 'teacherId', type: 'select', lookup: 'teachers', required: true },
        { key: 'subjectId', type: 'select', lookup: 'subjects', required: true },
      ],
    })
    expect(teacherUnavailability).toMatchObject({
      endpoint: '/api/admin/teacher-unavailability',
      fields: [
        { key: 'teacherId', type: 'select', lookup: 'teachers', required: true },
        { key: 'dayOfWeek', type: 'select', required: true },
        { key: 'unavailableFrom', type: 'time', required: true },
        { key: 'unavailableTo', type: 'time', required: true },
      ],
    })
    expect(teacherUnavailability?.fields[1]?.options).toHaveLength(5)
  })

  it('renders teacher workflow links in admin navigation', async () => {
    const pinia = createPinia()
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/admin', name: 'admin-dashboard', component: { template: '<div />' } },
        { path: '/admin/schedules', name: 'admin-schedules', component: { template: '<div />' } },
        {
          path: '/admin/exam-schedules',
          name: 'admin-exam-schedules',
          component: { template: '<div />' },
        },
        {
          path: '/admin/action-log',
          name: 'admin-action-log',
          component: { template: '<div />' },
        },
        {
          path: '/admin/generation-jobs',
          name: 'admin-generation-jobs',
          component: { template: '<div />' },
        },
        {
          path: '/admin/entities/:entity',
          name: 'admin-entity',
          component: { template: '<div />' },
        },
      ],
    })
    setActivePinia(pinia)
    await router.push('/admin')
    await router.isReady()

    const wrapper = mount(AdminLayout, {
      global: {
        plugins: [pinia, router],
      },
    })

    expect(wrapper.text()).toContain('Предмети викладачів')
    expect(wrapper.text()).toContain('Недоступність викладачів')
    expect(wrapper.html()).toContain('/admin/entities/teacher-subjects')
    expect(wrapper.html()).toContain('/admin/entities/teacher-unavailability')
  })

  it('maps API validation problems into ApiError violations', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response(
            JSON.stringify({
              title: 'Validation failed',
              violations: [{ propertyPath: 'name', message: 'This value should not be blank.' }],
            }),
            { status: 422, headers: { 'Content-Type': 'application/json' } },
          ),
      ),
    )

    await expect(requestJson('/api/admin/groups', { method: 'POST' })).rejects.toMatchObject({
      status: 422,
      violations: [{ propertyPath: 'name', message: 'This value should not be blank.' }],
    } satisfies Partial<ApiError>)
  })

  it('maps API errors objects into ApiError violations', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response(
            JSON.stringify({
              title: 'Validation failed',
              errors: { schedule: 'Only draft schedules can be edited.' },
            }),
            { status: 422, headers: { 'Content-Type': 'application/json' } },
          ),
      ),
    )

    await expect(
      requestJson('/api/admin/schedules/12/entries', { method: 'POST' }),
    ).rejects.toMatchObject({
      status: 422,
      violations: [{ propertyPath: 'schedule', message: 'Only draft schedules can be edited.' }],
    } satisfies Partial<ApiError>)
  })
})

function mockFetch(): typeof fetch {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = String(input)

    if (url.includes('/api/public/groups')) {
      return jsonResponse({
        items: [
          { id: 1, name: 'КН-22', speciality: "Комп'ютерні науки", course: 4, studentCount: 24 },
        ],
      })
    }

    if (url.includes('/api/public/teachers')) {
      return jsonResponse({
        items: [{ id: 7, firstName: 'Іван', lastName: 'Петренко', department: 'Інформатика' }],
      })
    }

    if (url.includes('/api/public/rooms')) {
      return jsonResponse({
        items: [{ id: 3, name: 'Лаб 1', type: 'computer', capacity: 30 }],
      })
    }

    const weekStart = currentWeekStart()

    return jsonResponse({
      weekStart,
      type: 'group',
      id: 1,
      items: [
        {
          id: 123,
          date: weekStart,
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
      ],
    })
  }) as typeof fetch
}

function mockAuthFetch(): typeof fetch {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = String(input)

    if (url.includes('/api/auth/login')) {
      return jsonResponse({
        token: 'jwt-token',
        user: userResponse,
      })
    }

    return jsonResponse({ user: userResponse })
  }) as typeof fetch
}

function jsonResponse(body: unknown): Response {
  return new Response(JSON.stringify(body), {
    status: 200,
    headers: { 'Content-Type': 'application/json' },
  })
}

async function flushPromises(): Promise<void> {
  await new Promise((resolve) => window.setTimeout(resolve, 0))
  await new Promise((resolve) => window.setTimeout(resolve, 0))
}

const userResponse = {
  id: 1,
  firstName: 'Ada',
  lastName: 'Lovelace',
  email: 'admin@example.com',
  role: 'admin',
}
