export type Role = 'ROLE_REPORTER' | 'ROLE_AGENT' | 'ROLE_MANAGER'

export type TicketStatus = 'open' | 'in_progress' | 'pending' | 'resolved' | 'closed'
export type TicketPriority = 'low' | 'medium' | 'high' | 'critical'
export type SlaStatus = 'ok' | 'warning' | 'breached'

export interface User {
  id: string
  email: string
  name: string
  roles: Role[]
}

export interface Category {
  id: string
  name: string
}

export interface SlaPolicy {
  id: string
  name: string
  responseTime: number
  resolutionTime: number
}

export interface Ticket {
  id: string
  title: string
  description: string
  status: TicketStatus
  priority: TicketPriority
  category: string | null       // IRI: /api/categories/{id}
  categoryName: string | null
  reporter: string              // IRI: /api/users/{id}
  reporterName: string
  assignee: string | null       // IRI or null
  assigneeName: string | null
  responseSlaStatus: SlaStatus | null
  resolutionSlaStatus: SlaStatus | null
  responseDueAt: string | null
  resolutionDueAt: string | null
  respondedAt: string | null
  resolvedAt: string | null
  createdAt: string
  updatedAt: string
}

export interface Comment {
  id: string
  body: string
  isInternal: boolean
  author: string                // IRI: /api/users/{id}
  authorName: string
  createdAt: string
}

export interface AuditLog {
  id: string
  action: string
  oldValue: string | null
  newValue: string | null
  user: string                  // IRI: /api/users/{id}
  createdAt: string
}

export interface DashboardSummary {
  byStatus: Record<TicketStatus, number>
  slaViolations: number
}

export interface DashboardAgent {
  agent: User
  openTickets: number
  resolvedToday: number
}

export interface PaginatedResponse<T> {
  'hydra:member': T[]
  'hydra:totalItems': number
}

export interface TicketFilters {
  status?: TicketStatus
  priority?: TicketPriority
  assignee?: string
  slaStatus?: 'ok' | 'at_risk' | 'violated'
  page?: number
}
