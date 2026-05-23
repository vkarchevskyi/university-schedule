export interface AdminActionLogUser {
  id: number
  firstName: string
  lastName: string
  email: string
}

export interface AdminActionLog {
  id: number
  action: string
  entityType: string
  entityId: number
  createdAt: string
  user: AdminActionLogUser
  beforePayload: Record<string, unknown> | null
  afterPayload: Record<string, unknown> | null
}
