import { requestJson } from '@/api/http'
import type { AdminEntity, ResourceCollection } from '@/types/adminEntities'

export function listEntities(endpoint: string): Promise<ResourceCollection<AdminEntity>> {
  return requestJson(endpoint, { authenticated: true })
}

export function createEntity(endpoint: string, payload: AdminEntity): Promise<AdminEntity> {
  return requestJson(endpoint, {
    method: 'POST',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function updateEntity(
  endpoint: string,
  id: number,
  payload: AdminEntity,
): Promise<AdminEntity> {
  return requestJson(`${endpoint}/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
    authenticated: true,
  })
}

export function deleteEntity(endpoint: string, id: number): Promise<void> {
  return requestJson(`${endpoint}/${id}`, {
    method: 'DELETE',
    authenticated: true,
  })
}
