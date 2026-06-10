import { apiClient } from './client'
import type { DashboardSummary, DashboardAgent } from './types'

export async function getDashboardSummary(): Promise<DashboardSummary> {
  const { data } = await apiClient.get<DashboardSummary>('/dashboard/summary')
  return data
}

export async function getDashboardAgents(): Promise<DashboardAgent[]> {
  const { data } = await apiClient.get<DashboardAgent[]>('/dashboard/agents')
  return data
}
