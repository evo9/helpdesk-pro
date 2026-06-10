import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getTickets } from '@/api/tickets'
import { useAuth } from '@/contexts/AuthContext'
import type { Ticket, TicketStatus, TicketPriority, SlaStatus } from '@/api/types'
import { PRIORITY_VARIANT, STATUS_LABEL } from '@/lib/ticketConstants'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import SlaTimer from '@/components/SlaTimer'

const STATUS_OPTIONS: { value: TicketStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'All' },
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'pending', label: 'Pending' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
]

const PRIORITY_OPTIONS: { value: TicketPriority | 'all'; label: string }[] = [
  { value: 'all', label: 'All' },
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
]

const SLA_OPTIONS: { value: SlaStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'All' },
  { value: 'ok', label: 'OK' },
  { value: 'warning', label: 'Warning' },
  { value: 'breached', label: 'Breached' },
]

export default function MyAssignedPage() {
  const { currentUser } = useAuth()
  const [statusFilter, setStatusFilter] = useState<TicketStatus | 'all'>('all')
  const [priorityFilter, setPriorityFilter] = useState<TicketPriority | 'all'>('all')
  const [slaFilter, setSlaFilter] = useState<SlaStatus | 'all'>('all')

  const { data, isLoading, error } = useQuery({
    queryKey: ['tickets'],
    queryFn: () => getTickets(),
  })

  const myIri = currentUser ? `/api/users/${currentUser.id}` : null

  const allTickets: Ticket[] = data ?? []
  const tickets = allTickets.filter((t) => {
    if (t.assignee !== myIri) return false
    if (statusFilter !== 'all' && t.status !== statusFilter) return false
    if (priorityFilter !== 'all' && t.priority !== priorityFilter) return false
    if (slaFilter !== 'all') {
      const activeSla =
        t.respondedAt === null ? t.responseSlaStatus : t.resolutionSlaStatus
      if (activeSla !== slaFilter) return false
    }
    return true
  })

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">My Assigned Tickets</h1>

      <div className="flex gap-2">
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value as TicketStatus | 'all')}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {STATUS_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>

        <select
          value={priorityFilter}
          onChange={(e) => setPriorityFilter(e.target.value as TicketPriority | 'all')}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {PRIORITY_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>

        <select
          value={slaFilter}
          onChange={(e) => setSlaFilter(e.target.value as SlaStatus | 'all')}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {SLA_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>Failed to load tickets.</AlertDescription>
        </Alert>
      )}

      {isLoading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : (
        <div className="overflow-hidden rounded-lg border">
          <table className="w-full text-sm">
            <thead className="bg-muted text-muted-foreground">
              <tr>
                <th className="px-4 py-2 text-left font-medium">Title</th>
                <th className="px-4 py-2 text-left font-medium">Category</th>
                <th className="px-4 py-2 text-left font-medium">Priority</th>
                <th className="px-4 py-2 text-left font-medium">Status</th>
                <th className="px-4 py-2 text-left font-medium">SLA</th>
                <th className="px-4 py-2 text-left font-medium">Created</th>
              </tr>
            </thead>
            <tbody>
              {tickets.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-6 text-center text-muted-foreground">
                    No assigned tickets.
                  </td>
                </tr>
              ) : (
                tickets.map((ticket) => {
                  const slaDueAt =
                    ticket.respondedAt === null
                      ? ticket.responseDueAt
                      : ticket.resolutionDueAt
                  const slaStatus =
                    ticket.respondedAt === null
                      ? ticket.responseSlaStatus
                      : ticket.resolutionSlaStatus
                  return (
                    <tr key={ticket.id} className="border-t hover:bg-muted/50">
                      <td className="px-4 py-2">
                        <Link
                          to={`/tickets/${ticket.id}`}
                          className="font-medium text-primary hover:underline"
                        >
                          {ticket.title}
                        </Link>
                      </td>
                      <td className="px-4 py-2 text-muted-foreground">
                        {ticket.categoryName ?? '—'}
                      </td>
                      <td className="px-4 py-2">
                        <Badge variant={PRIORITY_VARIANT[ticket.priority]}>
                          {ticket.priority}
                        </Badge>
                      </td>
                      <td className="px-4 py-2">
                        <Badge variant="secondary">{STATUS_LABEL[ticket.status]}</Badge>
                      </td>
                      <td className="px-4 py-2">
                        <SlaTimer dueAt={slaDueAt} slaStatus={slaStatus} />
                      </td>
                      <td className="px-4 py-2 text-muted-foreground">
                        {new Date(ticket.createdAt).toLocaleDateString()}
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
