import { apiClient } from './client'
import type { Ticket, Comment, AuditLog, PaginatedResponse, TicketFilters } from './types'

export async function getTickets(filters?: TicketFilters): Promise<Ticket[]> {
  const { data } = await apiClient.get<PaginatedResponse<Ticket>>('/tickets', { params: filters })
  return data.member
}

export async function getTicket(id: string): Promise<Ticket> {
  const { data } = await apiClient.get<Ticket>(`/tickets/${id}`)
  return data
}

export async function createTicket(payload: {
  title: string
  description: string
  priority: string
  category?: string
}): Promise<Ticket> {
  const { data } = await apiClient.post<Ticket>('/tickets', payload)
  return data
}

export async function updateTicket(
  id: string,
  payload: Partial<{ status: string; priority: string; assignee: string | null }>
): Promise<Ticket> {
  const { data } = await apiClient.patch<Ticket>(`/tickets/${id}`, payload)
  return data
}

export async function deleteTicket(id: string): Promise<void> {
  await apiClient.delete(`/tickets/${id}`)
}

export async function getComments(ticketId: string): Promise<Comment[]> {
  const { data } = await apiClient.get<PaginatedResponse<Comment>>(
    `/tickets/${ticketId}/comments`
  )
  return data.member
}

export async function createComment(
  ticketId: string,
  payload: { body: string; isInternal?: boolean }
): Promise<Comment> {
  const { data } = await apiClient.post<Comment>(`/tickets/${ticketId}/comments`, payload)
  return data
}

export async function getAuditLog(ticketId: string): Promise<AuditLog[]> {
  const { data } = await apiClient.get<PaginatedResponse<AuditLog>>(`/tickets/${ticketId}/audit`)
  return data.member
}
