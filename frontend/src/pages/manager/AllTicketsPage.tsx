import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getTickets } from '@/api/tickets'
import { getUsers } from '@/api/users'
import type { Ticket, User, TicketStatus, TicketPriority, SlaStatus } from '@/api/types'
import { PRIORITY_VARIANT, STATUS_LABEL } from '@/lib/ticketConstants'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import SlaTimer from '@/components/SlaTimer'

const ITEMS_PER_PAGE = 20

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

export default function AllTicketsPage() {
  const [statusFilter, setStatusFilter] = useState<TicketStatus | 'all'>('all')
  const [priorityFilter, setPriorityFilter] = useState<TicketPriority | 'all'>('all')
  const [slaFilter, setSlaFilter] = useState<SlaStatus | 'all'>('all')
  const [assigneeFilter, setAssigneeFilter] = useState<string>('all')
  const [page, setPage] = useState(1)

  const { data: ticketsData, isLoading: ticketsLoading, error: ticketsError } = useQuery({
    queryKey: ['tickets'],
    queryFn: () => getTickets(),
  })

  const { data: usersData } = useQuery({
    queryKey: ['users'],
    queryFn: () => getUsers(),
  })

  const agents: User[] = (usersData ?? []).filter(
    (u: User) => u.role !== 'ROLE_REPORTER'
  )

  const allTickets: Ticket[] = ticketsData ?? []

  const filtered = allTickets.filter((t) => {
    if (statusFilter !== 'all' && t.status !== statusFilter) return false
    if (priorityFilter !== 'all' && t.priority !== priorityFilter) return false
    if (slaFilter !== 'all') {
      const activeSla =
        t.respondedAt === null ? t.responseSlaStatus : t.resolutionSlaStatus
      if (activeSla === null || activeSla !== slaFilter) return false
    }
    if (assigneeFilter !== 'all' && t.assignee !== `/api/users/${assigneeFilter}`) return false
    return true
  })

  const totalPages = Math.max(1, Math.ceil(filtered.length / ITEMS_PER_PAGE))
  const tickets = filtered.slice((page - 1) * ITEMS_PER_PAGE, page * ITEMS_PER_PAGE)

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">All Tickets</h1>

      <div className="flex flex-wrap gap-2">
        <select
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value as TicketStatus | 'all'); setPage(1) }}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {STATUS_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>

        <select
          value={priorityFilter}
          onChange={(e) => { setPriorityFilter(e.target.value as TicketPriority | 'all'); setPage(1) }}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {PRIORITY_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>

        <select
          value={slaFilter}
          onChange={(e) => { setSlaFilter(e.target.value as SlaStatus | 'all'); setPage(1) }}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {SLA_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>

        <select
          value={assigneeFilter}
          onChange={(e) => { setAssigneeFilter(e.target.value); setPage(1) }}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          <option value="all">All Assignees</option>
          {agents.map((u) => (
            <option key={u.id} value={u.id}>{u.fullName}</option>
          ))}
        </select>
      </div>

      {ticketsError && (
        <Alert variant="destructive">
          <AlertDescription>Failed to load tickets.</AlertDescription>
        </Alert>
      )}

      {ticketsLoading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : (
        <>
          <div className="overflow-hidden rounded-lg border">
            <table className="w-full text-sm">
              <thead className="bg-muted text-muted-foreground">
                <tr>
                  <th className="px-4 py-2 text-left font-medium">Title</th>
                  <th className="px-4 py-2 text-left font-medium">Reporter</th>
                  <th className="px-4 py-2 text-left font-medium">Assignee</th>
                  <th className="px-4 py-2 text-left font-medium">Priority</th>
                  <th className="px-4 py-2 text-left font-medium">Status</th>
                  <th className="px-4 py-2 text-left font-medium">SLA</th>
                  <th className="px-4 py-2 text-left font-medium">Created</th>
                </tr>
              </thead>
              <tbody>
                {tickets.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-4 py-6 text-center text-muted-foreground">
                      No tickets found.
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
                          {ticket.reporterName ?? '—'}
                        </td>
                        <td className="px-4 py-2 text-muted-foreground">
                          {ticket.assigneeName ?? 'Unassigned'}
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

          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page === 1}
              onClick={() => setPage((p) => p - 1)}
            >
              Prev
            </Button>
            <span className="text-sm text-muted-foreground">
              Page {page} of {totalPages}
            </span>
            <Button
              variant="outline"
              size="sm"
              disabled={page === totalPages}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
            </Button>
          </div>
        </>
      )}
    </div>
  )
}
