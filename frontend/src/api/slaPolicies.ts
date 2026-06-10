import { apiClient } from './client'
import type { SlaPolicy } from './types'

export async function getSlaPolicies(): Promise<SlaPolicy[]> {
  const { data } = await apiClient.get<SlaPolicy[]>('/sla-policies')
  return data
}

export async function createSlaPolicy(payload: {
  name: string
  responseTime: number
  resolutionTime: number
}): Promise<SlaPolicy> {
  const { data } = await apiClient.post<SlaPolicy>('/sla-policies', payload)
  return data
}

export async function updateSlaPolicy(
  id: number,
  payload: Partial<{ name: string; responseTime: number; resolutionTime: number }>
): Promise<SlaPolicy> {
  const { data } = await apiClient.patch<SlaPolicy>(`/sla-policies/${id}`, payload)
  return data
}

export async function deleteSlaPolicy(id: number): Promise<void> {
  await apiClient.delete(`/sla-policies/${id}`)
}
