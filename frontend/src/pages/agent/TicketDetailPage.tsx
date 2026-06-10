import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getTicket, updateTicket, getComments, createComment, getAuditLog } from '@/api/tickets'
import { getUsers } from '@/api/users'
import { useAuth } from '@/contexts/AuthContext'
import { PRIORITY_VARIANT, STATUS_LABEL, NEXT_STATUSES } from '@/lib/ticketConstants'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import SlaTimer from '@/components/SlaTimer'

type Tab = 'comments' | 'audit'

export default function TicketDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { currentUser, hasRole } = useAuth()

  const [activeTab, setActiveTab] = useState<Tab>('comments')
  const [selectedStatus, setSelectedStatus] = useState<string>('')
  const [selectedUserId, setSelectedUserId] = useState<string>('')
  const [commentBody, setCommentBody] = useState('')
  const [isInternal, setIsInternal] = useState(false)

  const {
    data: ticket,
    isLoading: ticketLoading,
    error: ticketError,
  } = useQuery({
    queryKey: ['ticket', id],
    queryFn: () => getTicket(id!),
    enabled: !!id,
  })

  const {
    data: commentsData,
    isLoading: commentsLoading,
    error: commentsError,
  } = useQuery({
    queryKey: ['comments', id],
    queryFn: () => getComments(id!),
    enabled: !!id,
  })

  const {
    data: auditData,
    isLoading: auditLoading,
    error: auditError,
  } = useQuery({
    queryKey: ['audit', id],
    queryFn: () => getAuditLog(id!),
    enabled: !!id && activeTab === 'audit',
  })

  const { data: usersData } = useQuery({
    queryKey: ['users'],
    queryFn: () => getUsers(),
    enabled: hasRole('ROLE_MANAGER'),
  })

  const statusMutation = useMutation({
    mutationFn: (status: string) => updateTicket(id!, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['ticket', id] })
      queryClient.invalidateQueries({ queryKey: ['tickets'] })
    },
  })

  const assignMutation = useMutation({
    mutationFn: (userId: string) =>
      updateTicket(id!, { assignee: `/api/users/${userId}` }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['ticket', id] })
      queryClient.invalidateQueries({ queryKey: ['tickets'] })
    },
  })

  const commentMutation = useMutation({
    mutationFn: () => createComment(id!, { body: commentBody, isInternal }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['comments', id] })
      setCommentBody('')
      setIsInternal(false)
    },
  })

  if (!id) {
    return (
      <Alert variant="destructive">
        <AlertDescription>Invalid ticket URL.</AlertDescription>
      </Alert>
    )
  }

  if (ticketLoading) {
    return <p className="text-sm text-muted-foreground">Loading…</p>
  }

  if (ticketError || !ticket) {
    return (
      <Alert variant="destructive">
        <AlertDescription>Failed to load ticket.</AlertDescription>
      </Alert>
    )
  }

  const nextStatuses = NEXT_STATUSES[ticket.status]
  const effectiveStatus = selectedStatus || (nextStatuses[0] ?? '')

  const canChangeStatus =
    hasRole('ROLE_MANAGER') ||
    (hasRole('ROLE_AGENT') &&
      !!currentUser &&
      ticket.assignee === `/api/users/${currentUser.id}`)

  const agents =
    usersData?.['hydra:member']?.filter((u) => u.role !== 'ROLE_REPORTER') ?? []

  const currentAssigneeUuid = ticket.assignee?.split('/').pop() ?? ''
  const effectiveAssigneeId = selectedUserId || currentAssigneeUuid

  const comments = commentsData?.['hydra:member'] ?? []
  const auditLogs = auditData?.['hydra:member'] ?? []

  return (
    <div className="space-y-6">
      {/* Back button */}
      <button
        onClick={() => navigate(-1)}
        className="text-sm text-muted-foreground hover:text-foreground"
      >
        ← Back
      </button>

      {/* Header */}
      <div className="space-y-2">
        <div className="flex flex-wrap items-center gap-2">
          <h1 className="text-xl font-semibold">{ticket.title}</h1>
          <Badge variant={PRIORITY_VARIANT[ticket.priority]}>{ticket.priority}</Badge>
          <Badge variant="secondary">{STATUS_LABEL[ticket.status]}</Badge>
        </div>

        <div className="flex flex-wrap items-center gap-4 text-sm">
          <span className="text-muted-foreground">
            Response SLA:{' '}
            <SlaTimer dueAt={ticket.responseDueAt} slaStatus={ticket.responseSlaStatus} />
          </span>
          <span className="text-muted-foreground">
            Resolution SLA:{' '}
            <SlaTimer dueAt={ticket.resolutionDueAt} slaStatus={ticket.resolutionSlaStatus} />
          </span>
        </div>
      </div>

      {/* Meta row */}
      <div className="flex flex-wrap gap-6 text-sm text-muted-foreground">
        <span>
          <span className="font-medium text-foreground">Category:</span>{' '}
          {ticket.categoryName ?? '—'}
        </span>
        <span>
          <span className="font-medium text-foreground">Reporter:</span> {ticket.reporterName}
        </span>
        <span>
          <span className="font-medium text-foreground">Assignee:</span>{' '}
          {ticket.assigneeName ?? 'Unassigned'}
        </span>
        <span>
          <span className="font-medium text-foreground">Created:</span>{' '}
          {new Date(ticket.createdAt).toLocaleString()}
        </span>
      </div>

      {/* Description */}
      <div className="rounded-lg border p-4">
        <p className="text-sm whitespace-pre-wrap">{ticket.description}</p>
      </div>

      {/* Status control */}
      {canChangeStatus && nextStatuses.length > 0 && (
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">Status:</span>
          <select
            value={effectiveStatus}
            onChange={(e) => setSelectedStatus(e.target.value)}
            className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
          >
            {nextStatuses.map((s) => (
              <option key={s} value={s}>
                {STATUS_LABEL[s]}
              </option>
            ))}
          </select>
          <Button
            size="sm"
            disabled={statusMutation.isPending}
            onClick={() => statusMutation.mutate(effectiveStatus)}
          >
            Update Status
          </Button>
          {statusMutation.isError && (
            <span className="text-sm text-destructive">Failed to update status.</span>
          )}
        </div>
      )}

      {/* Assign control (manager only) */}
      {hasRole('ROLE_MANAGER') && (
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">Assign to:</span>
          <select
            value={effectiveAssigneeId}
            onChange={(e) => setSelectedUserId(e.target.value)}
            className="h-8 rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
          >
            <option value="">— Unassigned —</option>
            {agents.map((u) => (
              <option key={u.id} value={u.id}>
                {u.fullName}
              </option>
            ))}
          </select>
          <Button
            size="sm"
            disabled={assignMutation.isPending || !effectiveAssigneeId}
            onClick={() => assignMutation.mutate(effectiveAssigneeId)}
          >
            Assign
          </Button>
          {assignMutation.isError && (
            <span className="text-sm text-destructive">Failed to assign ticket.</span>
          )}
        </div>
      )}

      {/* Tabs */}
      <div className="space-y-4">
        <div className="flex gap-4 border-b">
          <button
            className={`pb-2 text-sm font-medium ${
              activeTab === 'comments'
                ? 'border-b-2 border-primary text-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
            onClick={() => setActiveTab('comments')}
          >
            Comments
          </button>
          <button
            className={`pb-2 text-sm font-medium ${
              activeTab === 'audit'
                ? 'border-b-2 border-primary text-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
            onClick={() => setActiveTab('audit')}
          >
            Audit Log
          </button>
        </div>

        {/* Comments tab */}
        {activeTab === 'comments' && (
          <div className="space-y-4">
            {commentsError && (
              <Alert variant="destructive">
                <AlertDescription>Failed to load comments.</AlertDescription>
              </Alert>
            )}

            {commentsLoading ? (
              <p className="text-sm text-muted-foreground">Loading comments…</p>
            ) : comments.length === 0 ? (
              <p className="text-sm text-muted-foreground">No comments yet.</p>
            ) : (
              <div className="space-y-3">
                {comments.map((comment) => (
                  <div key={comment.id} className="rounded-lg border p-4 space-y-1">
                    <div className="flex items-center gap-2 text-sm">
                      <span className="font-medium">{comment.authorName}</span>
                      {comment.isInternal && (
                        <Badge variant="secondary">Internal</Badge>
                      )}
                      <span className="text-muted-foreground">
                        {new Date(comment.createdAt).toLocaleString()}
                      </span>
                    </div>
                    <p className="text-sm whitespace-pre-wrap">{comment.body}</p>
                  </div>
                ))}
              </div>
            )}

            {/* Add comment form */}
            <div className="space-y-2 rounded-lg border p-4">
              <Textarea
                placeholder="Write a comment…"
                value={commentBody}
                onChange={(e) => setCommentBody(e.target.value)}
                rows={3}
              />
              <div className="flex items-center justify-between">
                <label className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={isInternal}
                    onChange={(e) => setIsInternal(e.target.checked)}
                  />
                  Internal
                </label>
                <Button
                  size="sm"
                  disabled={commentMutation.isPending || commentBody.trim() === ''}
                  onClick={() => commentMutation.mutate()}
                >
                  Post
                </Button>
              </div>
              {commentMutation.isError && (
                <Alert variant="destructive">
                  <AlertDescription>Failed to post comment.</AlertDescription>
                </Alert>
              )}
            </div>
          </div>
        )}

        {/* Audit log tab */}
        {activeTab === 'audit' && (
          <div className="space-y-3">
            {auditError && (
              <Alert variant="destructive">
                <AlertDescription>Failed to load audit log.</AlertDescription>
              </Alert>
            )}

            {auditLoading ? (
              <p className="text-sm text-muted-foreground">Loading audit log…</p>
            ) : auditLogs.length === 0 ? (
              <p className="text-sm text-muted-foreground">No audit entries.</p>
            ) : (
              auditLogs.map((entry) => (
                <div key={entry.id} className="rounded-lg border p-4 space-y-1">
                  <div className="flex flex-wrap gap-3 text-sm">
                    <span className="text-muted-foreground">
                      {new Date(entry.createdAt).toLocaleString()}
                    </span>
                    <span className="font-medium">{entry.actorName}</span>
                    <span className="text-muted-foreground">{entry.action}</span>
                  </div>
                  {Object.keys(entry.payload).length > 0 && (
                    <pre className="text-xs bg-muted rounded p-2 overflow-x-auto">
                      {JSON.stringify(entry.payload, null, 2)}
                    </pre>
                  )}
                </div>
              ))
            )}
          </div>
        )}
      </div>
    </div>
  )
}
