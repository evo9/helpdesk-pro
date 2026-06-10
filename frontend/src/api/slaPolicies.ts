import { apiClient } from './client'
import type { SlaPolicy, PaginatedResponse } from './types'

export async function getSlaPolicies(): Promise<SlaPolicy[]> {
  const { data } = await apiClient.get<PaginatedResponse<SlaPolicy>>('/sla-policies')
  return data.member
}

export async function createSlaPolicy(payload: {
  category: string
  priority: string
  responseHours: number
  resolutionHours: number
}): Promise<SlaPolicy> {
  const { data } = await apiClient.post<SlaPolicy>('/sla-policies', payload)
  return data
}

export async function updateSlaPolicy(
  id: string,
  payload: Partial<{ responseHours: number; resolutionHours: number }>
): Promise<SlaPolicy> {
  const { data } = await apiClient.patch<SlaPolicy>(`/sla-policies/${id}`, payload)
  return data
}
