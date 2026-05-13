import { afterEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PublicSchedulePage from '@/components/pages/PublicSchedulePage.vue'
import { requestJson } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { addWeeks, currentWeekStart, mondayOfWeek, toIsoDate } from '@/utils/date'

describe('App', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    window.localStorage.clear()
  })

  it('renders the public schedule page', async () => {
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
    expect(auth.admin?.email).toBe('admin@example.com')
    expect(window.localStorage.getItem('university-schedule.admin-token')).toBe('jwt-token')

    auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.admin).toBeNull()
    expect(window.localStorage.getItem('university-schedule.admin-token')).toBeNull()
  })

  it('sends bearer token for authenticated requests', async () => {
    let authorizationHeader: string | null = null
    const fetchMock = vi.fn(async (_input: RequestInfo | URL, options?: RequestInit) => {
      const headers = options?.headers
      authorizationHeader = headers instanceof Headers ? headers.get('Authorization') : null

      return jsonResponse({ admin: adminResponse })
    })
    vi.stubGlobal('fetch', fetchMock)
    window.localStorage.setItem('university-schedule.admin-token', 'jwt-token')

    await requestJson('/api/auth/me', { authenticated: true })

    expect(authorizationHeader).toBe('Bearer jwt-token')
  })
})

function mockFetch(): typeof fetch {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = String(input)

    if (url.includes('/api/public/groups')) {
      return jsonResponse({
        items: [{ id: 1, name: 'КН-22', speciality: "Комп'ютерні науки", course: 4, studentCount: 24 }],
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
        admin: adminResponse,
      })
    }

    return jsonResponse({ admin: adminResponse })
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

const adminResponse = {
  id: 1,
  firstName: 'Ada',
  lastName: 'Lovelace',
  email: 'admin@example.com',
}
