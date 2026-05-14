const tokenStorageKey = 'university-schedule.admin-token'
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'

let unauthorizedHandler: (() => void) | null = null

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

  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...options,
    headers,
  })

  if (response.status === 401 && options.authenticated) {
    clearStoredToken()
    unauthorizedHandler?.()
  }

  if (!response.ok) {
    throw new Error(`Request failed with status ${response.status}`)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}
