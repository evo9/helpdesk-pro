import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getTicket, getComments, createComment, updateTicket } from '@/api/tickets'
import { PRIORITY_VARIANT, STATUS_LABEL } from '@/lib/ticketConstants'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import SlaIndicator from '@/components/SlaIndicator'

const SEVENTY_TWO_HOURS_MS = 72 * 60 * 60 * 1000

export default function TicketDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [commentBody, setCommentBody] = useState('')

  const {
    data: ticket,
    isLoading: ticketLoading,
    error: ticketError,
  } = useQuery({
    queryKey: ['ticket', id],
    queryFn: () => getTicket(id!),
    enabled: !!id,
  })

  const { data: commentsData } = useQuery({
    queryKey: ['comments', id],
    queryFn: () => getComments(id!),
    enabled: !!id,
  })
  const publicComments = (commentsData ?? []).filter(
    (c) => !c.isInternal
  )

  const addCommentMutation = useMutation({
    mutationFn: (body: string) => createComment(id!, { body }),
    onSuccess: () => {
      setCommentBody('')
      queryClient.invalidateQueries({ queryKey: ['comments', id] })
    },
  })

  const reopenMutation = useMutation({
    mutationFn: () => updateTicket(id!, { status: 'open' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['ticket', id] })
    },
  })

  if (ticketLoading) {
    return <p className="text-sm text-muted-foreground">Loading…</p>
  }

  if (ticketError || !ticket) {
    return (
      <Alert variant="destructive">
        <AlertDescription>Ticket not found or you do not have access.</AlertDescription>
      </Alert>
    )
  }

  const canReopen =
    ticket.status === 'resolved' &&
    ticket.resolvedAt !== null &&
    Date.now() - new Date(ticket.resolvedAt).getTime() < SEVENTY_TWO_HOURS_MS

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* Header */}
      <div className="space-y-2">
        <div className="flex items-start justify-between gap-4">
          <h1 className="text-xl font-semibold">{ticket.title}</h1>
          <div className="flex shrink-0 items-center gap-2">
            <Badge variant={PRIORITY_VARIANT[ticket.priority]}>{ticket.priority}</Badge>
            <Badge variant="secondary">{STATUS_LABEL[ticket.status]}</Badge>
            <SlaIndicator
              responseSlaStatus={ticket.responseSlaStatus}
              resolutionSlaStatus={ticket.resolutionSlaStatus}
            />
          </div>
        </div>
        <dl className="text-sm text-muted-foreground">
          <div className="flex gap-4">
            {ticket.categoryName && (
              <span>Category: <span className="text-foreground">{ticket.categoryName}</span></span>
            )}
            <span>Created: <span className="text-foreground">{new Date(ticket.createdAt).toLocaleString()}</span></span>
          </div>
        </dl>
      </div>

      {/* Description */}
      <div className="rounded-lg border p-4">
        <p className="whitespace-pre-wrap text-sm">{ticket.description}</p>
      </div>

      {/* Reopen */}
      {canReopen && (
        <Button
          variant="outline"
          disabled={reopenMutation.isPending}
          onClick={() => reopenMutation.mutate()}
        >
          {reopenMutation.isPending ? 'Reopening…' : 'Reopen Ticket'}
        </Button>
      )}

      {/* Comments */}
      <div className="space-y-4">
        <h2 className="font-medium">Comments</h2>

        {publicComments.length === 0 ? (
          <p className="text-sm text-muted-foreground">No comments yet.</p>
        ) : (
          <div className="space-y-3">
            {publicComments.map((comment) => (
              <div key={comment.id} className="rounded-lg border p-3 text-sm">
                <div className="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                  <span>{comment.authorName}</span>
                  <span>{new Date(comment.createdAt).toLocaleString()}</span>
                </div>
                <p className="whitespace-pre-wrap">{comment.body}</p>
              </div>
            ))}
          </div>
        )}

        {/* Add comment form */}
        <form
          onSubmit={(e) => {
            e.preventDefault()
            if (commentBody.trim()) addCommentMutation.mutate(commentBody.trim())
          }}
          className="space-y-2"
        >
          <Textarea
            value={commentBody}
            onChange={(e) => setCommentBody(e.target.value)}
            placeholder="Add a comment…"
          />
          {addCommentMutation.error && (
            <Alert variant="destructive">
              <AlertDescription>Failed to post comment.</AlertDescription>
            </Alert>
          )}
          <Button
            type="submit"
            disabled={addCommentMutation.isPending || !commentBody.trim()}
          >
            {addCommentMutation.isPending ? 'Posting…' : 'Post Comment'}
          </Button>
        </form>
      </div>

      {/* Back link */}
      <button
        type="button"
        className="text-sm text-muted-foreground hover:text-foreground"
        onClick={() => navigate('/my-tickets')}
      >
        ← Back to My Tickets
      </button>
    </div>
  )
}
