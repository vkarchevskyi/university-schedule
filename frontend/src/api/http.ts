const tokenStorageKey = 'university-schedule.user-token'
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'

let unauthorizedHandler: (() => void) | null = null

export interface ApiViolation {
  propertyPath: string
  message: string
}

export class ApiError extends Error {
  public constructor(
    public readonly status: number,
    message: string,
    public readonly violations: ApiViolation[] = [],
  ) {
    super(message)
  }
}

export function setUnauthorizedHandler(handler: () => void): void {
  unauthorizedHandler = handler
}

export function getStoredToken(): string | null {
  return window.localStorage.getItem(tokenStorageKey)
}

export function setStoredToken(token: string): void {
  window.localStorage.setItem(tokenStorageKey, token)
}

export function clearStoredToken(): void {
  window.localStorage.removeItem(tokenStorageKey)
}

export function buildApiUrl(path: string): string {
  const normalizedBaseUrl = apiBaseUrl.replace(/\/+$/, '')
  const normalizedPath = path.replace(/^\/+/, '')

  if (normalizedBaseUrl.endsWith('/api') && normalizedPath.startsWith('api/')) {
    return `${normalizedBaseUrl}/${normalizedPath.slice(4)}`
  }

  return `${normalizedBaseUrl}/${normalizedPath}`
}

export function buildWebSocketUrl(path: string, query: Record<string, string> = {}): string {
  const url = new URL(buildApiUrl(path))
  url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:'

  for (const [key, value] of Object.entries(query)) {
    url.searchParams.set(key, value)
  }

  return url.toString()
}

export async function requestJson<T>(
  path: string,
  options: RequestInit & { authenticated?: boolean } = {},
): Promise<T> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body !== undefined && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  if (options.authenticated) {
    const token = getStoredToken()
    if (token !== null) {
      headers.set('Authorization', `Bearer ${token}`)
    }
  }

  const response = await fetch(buildApiUrl(path), {
    ...options,
    headers,
  })

  if (response.status === 401 && options.authenticated) {
    clearStoredToken()
    unauthorizedHandler?.()
  }

  if (!response.ok) {
    let message = `Request failed with status ${response.status}`
    let violations: ApiViolation[] = []

    try {
      const payload = (await response.json()) as {
        title?: string
        detail?: string
        violations?: ApiViolation[]
      }
      message = payload.detail ?? payload.title ?? message
      violations = payload.violations ?? []
    } catch {
      // Keep the generic transport message when the response is not JSON.
    }

    throw new ApiError(response.status, message, violations)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}
