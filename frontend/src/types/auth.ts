export interface AdminProfile {
  id: number
  firstName: string
  lastName: string
  email: string
}

export interface LoginResponse {
  token: string
  admin: AdminProfile
}

export interface CurrentAdminResponse {
  admin: AdminProfile
}
