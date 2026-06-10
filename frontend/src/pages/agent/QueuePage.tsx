import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getTickets, updateTicket } from '@/api/tickets'
import { useAuth } from '@/contexts/AuthContext'
import type { TicketStatus, Ticket } from '@/api/types'
import { PRIORITY_VARIANT, STATUS_LABEL } from '@/lib/ticketConstants'
import { Button } from '@/components/ui/button'
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

export default function QueuePage() {
  const { currentUser } = useAuth()
  const queryClient = useQueryClient()
  const [statusFilter, setStatusFilter] = useState<TicketStatus | 'all'>('open')

  const { data, isLoading, error } = useQuery({
    queryKey: ['tickets'],
    queryFn: () => getTickets(),
  })

  const takeMutation = useMutation({
    mutationFn: (id: string) =>
      updateTicket(id, { assignee: `/api/users/${currentUser!.id}` }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['tickets'] }),
  })

  const allTickets: Ticket[] = data ?? []
  const unassigned = allTickets.filter((t) => t.assignee === null)
  const tickets =
    statusFilter === 'all'
      ? unassigned
      : unassigned.filter((t) => t.status === statusFilter)

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Ticket Queue</h1>

      <div>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value as TicketStatus | 'all')}
          className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
        >
          {STATUS_OPTIONS.map((o) => (
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
                <th className="px-4 py-2 text-left font-medium"></th>
              </tr>
            </thead>
            <tbody>
              {tickets.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-4 py-6 text-center text-muted-foreground">
                    No tickets in queue.
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
                      <td className="px-4 py-2">
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={
                            !currentUser ||
                            (takeMutation.isPending && takeMutation.variables === ticket.id)
                          }
                          onClick={() => takeMutation.mutate(ticket.id)}
                        >
                          Take
                        </Button>
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
