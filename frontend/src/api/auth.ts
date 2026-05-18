import { requestJson } from '@/api/http'
import type { CurrentUserResponse, LoginResponse } from '@/types/auth'

export function loginAdmin(email: string, password: string): Promise<LoginResponse> {
  return requestJson<LoginResponse>('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function getCurrentUser(): Promise<CurrentUserResponse> {
  return requestJson<CurrentUserResponse>('/api/auth/me', {
    authenticated: true,
  })
}
