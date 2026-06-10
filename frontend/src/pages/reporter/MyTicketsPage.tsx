import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getTickets } from '@/api/tickets'
import type { TicketStatus } from '@/api/types'
import { PRIORITY_VARIANT, STATUS_LABEL } from '@/lib/ticketConstants'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import SlaIndicator from '@/components/SlaIndicator'

const STATUS_OPTIONS: { value: TicketStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'All' },
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'pending', label: 'Pending' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
]

export default function MyTicketsPage() {
  const navigate = useNavigate()
  const [statusFilter, setStatusFilter] = useState<TicketStatus | 'all'>('all')

  const { data, isLoading, error } = useQuery({
    queryKey: ['tickets', statusFilter],
    queryFn: () =>
      getTickets(statusFilter === 'all' ? undefined : { status: statusFilter }),
  })

  const tickets = data?.['hydra:member'] ?? []

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">My Tickets</h1>
        <Button onClick={() => navigate('/my-tickets/new')}>New Ticket</Button>
      </div>

      <div>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value as TicketStatus | 'all')}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {STATUS_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
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
                    No tickets found.
                  </td>
                </tr>
              ) : (
                tickets.map((ticket) => (
                  <tr key={ticket.id} className="border-t hover:bg-muted/50">
                    <td className="px-4 py-2">
                      <Link
                        to={`/my-tickets/${ticket.id}`}
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
                      <SlaIndicator
                        responseSlaStatus={ticket.responseSlaStatus}
                        resolutionSlaStatus={ticket.resolutionSlaStatus}
                      />
                    </td>
                    <td className="px-4 py-2 text-muted-foreground">
                      {new Date(ticket.createdAt).toLocaleDateString()}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
