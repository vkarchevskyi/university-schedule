import { requestJson } from '@/api/http'
import type { CurrentAdminResponse, LoginResponse } from '@/types/auth'

export function loginAdmin(email: string, password: string): Promise<LoginResponse> {
  return requestJson<LoginResponse>('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function getCurrentAdmin(): Promise<CurrentAdminResponse> {
  return requestJson<CurrentAdminResponse>('/api/auth/me', {
    authenticated: true,
  })
}
