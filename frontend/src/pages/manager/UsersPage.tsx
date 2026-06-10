import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getUsers, createUser, updateUser } from '@/api/users'
import type { User } from '@/api/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Dialog, DialogContent } from '@/components/ui/dialog'

const ROLE_OPTIONS = [
  { value: 'ROLE_REPORTER', label: 'Reporter' },
  { value: 'ROLE_AGENT', label: 'Agent' },
  { value: 'ROLE_MANAGER', label: 'Manager' },
]

const SELECT_CLASS =
  'h-7 rounded border border-input bg-background px-2 text-sm outline-none focus-visible:border-ring'

type CreateForm = { fullName: string; email: string; password: string; role: string }
const EMPTY_FORM: CreateForm = {
  fullName: '',
  email: '',
  password: '',
  role: 'ROLE_REPORTER',
}

export default function UsersPage() {
  const queryClient = useQueryClient()
  const [dialogOpen, setDialogOpen] = useState(false)
  const [form, setForm] = useState<CreateForm>(EMPTY_FORM)

  const { data, isLoading, error } = useQuery({
    queryKey: ['users'],
    queryFn: getUsers,
  })

  const createMutation = useMutation({
    mutationFn: createUser,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
      setDialogOpen(false)
      setForm(EMPTY_FORM)
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({
      id,
      payload,
    }: {
      id: string
      payload: Parameters<typeof updateUser>[1]
    }) => updateUser(id, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  })

  const users: User[] = data ?? []

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    createMutation.mutate(form)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Users</h1>
        <Button onClick={() => setDialogOpen(true)}>Add User</Button>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>Failed to load users.</AlertDescription>
        </Alert>
      )}

      {isLoading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : (
        <div className="overflow-hidden rounded-lg border">
          <table className="w-full text-sm">
            <thead className="bg-muted text-muted-foreground">
              <tr>
                <th className="px-4 py-2 text-left font-medium">Name</th>
                <th className="px-4 py-2 text-left font-medium">Email</th>
                <th className="px-4 py-2 text-left font-medium">Role</th>
                <th className="px-4 py-2 text-left font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              {users.length === 0 ? (
                <tr>
                  <td
                    colSpan={4}
                    className="px-4 py-6 text-center text-muted-foreground"
                  >
                    No users.
                  </td>
                </tr>
              ) : (
                users.map((user) => (
                  <tr key={user.id} className="border-t hover:bg-muted/50">
                    <td className="px-4 py-2 font-medium">{user.fullName}</td>
                    <td className="px-4 py-2 text-muted-foreground">{user.email}</td>
                    <td className="px-4 py-2">
                      <select
                        value={user.role}
                        onChange={(e) =>
                          updateMutation.mutate({
                            id: user.id,
                            payload: { role: e.target.value },
                          })
                        }
                        className={SELECT_CLASS}
                      >
                        {ROLE_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </td>
                    <td className="px-4 py-2">
                      <Button
                        size="sm"
                        variant={user.isActive ? 'default' : 'outline'}
                        onClick={() =>
                          updateMutation.mutate({
                            id: user.id,
                            payload: { isActive: !user.isActive },
                          })
                        }
                      >
                        {user.isActive ? 'Active' : 'Inactive'}
                      </Button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent
          title="Add User"
          footer={
            <>
              <Button variant="outline" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button
                type="submit"
                form="create-user-form"
                disabled={createMutation.isPending}
              >
                {createMutation.isPending ? 'Creating…' : 'Create'}
              </Button>
            </>
          }
        >
          <form id="create-user-form" onSubmit={handleSubmit} className="space-y-3">
            <div className="space-y-1">
              <Label htmlFor="u-fullName">Full Name</Label>
              <Input
                id="u-fullName"
                value={form.fullName}
                onChange={(e) => setForm({ ...form, fullName: e.target.value })}
                required
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="u-email">Email</Label>
              <Input
                id="u-email"
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                required
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="u-password">Password</Label>
              <Input
                id="u-password"
                type="password"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                required
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="u-role">Role</Label>
              <select
                id="u-role"
                value={form.role}
                onChange={(e) => setForm({ ...form, role: e.target.value })}
                className="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring"
              >
                {ROLE_OPTIONS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </div>
            {createMutation.isError && (
              <Alert variant="destructive">
                <AlertDescription>Failed to create user.</AlertDescription>
              </Alert>
            )}
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
