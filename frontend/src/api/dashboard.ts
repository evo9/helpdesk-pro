import { apiClient } from './client'
import type { DashboardSummary, DashboardAgent, TicketsByCategoryItem } from './types'

export async function getDashboardSummary(): Promise<DashboardSummary> {
  const { data } = await apiClient.get<DashboardSummary>('/dashboard/summary')
  return data
}

export async function getDashboardAgents(): Promise<DashboardAgent[]> {
  const { data } = await apiClient.get<{ member: DashboardAgent[] }>('/dashboard/agents')
  return data.member
}

export async function getTicketsByCategory(): Promise<TicketsByCategoryItem[]> {
  const { data } = await apiClient.get<{ member: TicketsByCategoryItem[] }>('/dashboard/tickets-by-category')
  return data.member
}
