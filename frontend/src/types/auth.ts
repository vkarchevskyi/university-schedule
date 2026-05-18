export type UserRole = 'user' | 'admin'

export interface UserProfile {
  id: number
  firstName: string
  lastName: string
  email: string
  role: UserRole
}

export interface LoginResponse {
  token: string
  user: UserProfile
}

export interface CurrentUserResponse {
  user: UserProfile
}
