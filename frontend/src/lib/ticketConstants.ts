import type { TicketPriority, TicketStatus } from '@/api/types'

export const PRIORITY_VARIANT: Record<TicketPriority, 'secondary' | 'default' | 'warning' | 'destructive'> = {
  low: 'secondary',
  medium: 'default',
  high: 'warning',
  critical: 'destructive',
}

export const STATUS_LABEL: Record<TicketStatus, string> = {
  open: 'Open',
  in_progress: 'In Progress',
  pending: 'Pending',
  resolved: 'Resolved',
  closed: 'Closed',
}

export const NEXT_STATUSES: Record<TicketStatus, TicketStatus[]> = {
  open:        ['in_progress'],
  in_progress: ['pending', 'resolved'],
  pending:     ['in_progress', 'resolved'],
  resolved:    ['closed'],
  closed:      [],
}
