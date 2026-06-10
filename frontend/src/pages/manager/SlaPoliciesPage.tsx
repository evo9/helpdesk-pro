import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getCategories } from '@/api/categories'
import { getSlaPolicies, createSlaPolicy, updateSlaPolicy } from '@/api/slaPolicies'
import type { Category, SlaPolicy } from '@/api/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Dialog, DialogContent } from '@/components/ui/dialog'

const PRIORITIES = ['low', 'medium', 'high', 'critical'] as const
type Priority = (typeof PRIORITIES)[number]

type PolicyForm = { responseHours: string; resolutionHours: string }

export default function SlaPoliciesPage() {
  const queryClient = useQueryClient()
  const [dialogOpen, setDialogOpen] = useState(false)
  const [selectedCell, setSelectedCell] = useState<{
    category: Category
    priority: Priority
    existing: SlaPolicy | null
  } | null>(null)
  const [form, setForm] = useState<PolicyForm>({
    responseHours: '',
    resolutionHours: '',
  })

  const { data: categoriesData, isLoading: catsLoading } = useQuery({
    queryKey: ['categories'],
    queryFn: getCategories,
  })

  const {
    data: policies = [],
    isLoading: policiesLoading,
    error,
  } = useQuery({
    queryKey: ['sla-policies'],
    queryFn: getSlaPolicies,
  })

  const saveMutation = useMutation({
    mutationFn: (vars: {
      existing: SlaPolicy | null
      category: Category
      priority: Priority
      responseHours: number
      resolutionHours: number
    }) => {
      if (vars.existing) {
        return updateSlaPolicy(vars.existing.id, {
          responseHours: vars.responseHours,
          resolutionHours: vars.resolutionHours,
        })
      }
      return createSlaPolicy({
        category: `/api/categories/${vars.category.id}`,
        priority: vars.priority,
        responseHours: vars.responseHours,
        resolutionHours: vars.resolutionHours,
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sla-policies'] })
      setDialogOpen(false)
    },
  })

  const categories: Category[] = (
    categoriesData ?? []
  ).filter((c: Category) => c.isActive !== false)

  function openCell(category: Category, priority: Priority) {
    const catIri = `/api/categories/${category.id}`
    const existing =
      policies.find(
        (p) => p.category === catIri && p.priority === priority
      ) ?? null
    setSelectedCell({ category, priority, existing })
    setForm({
      responseHours: existing ? String(existing.responseHours) : '',
      resolutionHours: existing ? String(existing.resolutionHours) : '',
    })
    setDialogOpen(true)
  }

  function closeDialog() {
    setDialogOpen(false)
    setSelectedCell(null)
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!selectedCell) return
    saveMutation.mutate({
      existing: selectedCell.existing,
      category: selectedCell.category,
      priority: selectedCell.priority,
      responseHours: Number(form.responseHours),
      resolutionHours: Number(form.resolutionHours),
    })
  }

  const isLoading = catsLoading || policiesLoading

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">SLA Policies</h1>
      <p className="text-sm text-muted-foreground">
        Click any cell to set response and resolution times. Values are in hours.
      </p>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>Failed to load SLA policies.</AlertDescription>
        </Alert>
      )}

      {isLoading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm border rounded-lg overflow-hidden">
            <thead className="bg-muted text-muted-foreground">
              <tr>
                <th className="px-4 py-2 text-left font-medium">Category</th>
                {PRIORITIES.map((p) => (
                  <th key={p} className="px-4 py-2 text-left font-medium capitalize">
                    {p}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {categories.length === 0 ? (
                <tr>
                  <td
                    colSpan={5}
                    className="px-4 py-6 text-center text-muted-foreground"
                  >
                    No active categories.
                  </td>
                </tr>
              ) : (
                categories.map((cat) => (
                  <tr key={cat.id} className="border-t hover:bg-muted/50">
                    <td className="px-4 py-2 font-medium">{cat.name}</td>
                    {PRIORITIES.map((priority) => {
                      const catIri = `/api/categories/${cat.id}`
                      const policy = policies.find(
                        (p) => p.category === catIri && p.priority === priority
                      )
                      return (
                        <td key={priority} className="px-4 py-2">
                          <button
                            onClick={() => openCell(cat, priority)}
                            className={`text-sm hover:underline underline-offset-2 ${
                              policy
                                ? 'text-foreground'
                                : 'text-muted-foreground'
                            }`}
                          >
                            {policy
                              ? `${policy.responseHours}h / ${policy.resolutionHours}h`
                              : '—'}
                          </button>
                        </td>
                      )
                    })}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}

      <Dialog open={dialogOpen} onOpenChange={(open) => { if (!open) closeDialog() }}>
        <DialogContent
          title={
            selectedCell?.existing ? 'Edit SLA Policy' : 'Create SLA Policy'
          }
          footer={
            <>
              <Button variant="outline" onClick={closeDialog}>
                Cancel
              </Button>
              <Button
                type="submit"
                form="sla-form"
                disabled={saveMutation.isPending}
              >
                {saveMutation.isPending ? 'Saving…' : 'Save'}
              </Button>
            </>
          }
        >
          {selectedCell && (
            <form id="sla-form" onSubmit={handleSubmit} className="space-y-3">
              <div className="flex gap-4 text-sm text-muted-foreground">
                <span>
                  <strong>Category:</strong> {selectedCell.category.name}
                </span>
                <span className="capitalize">
                  <strong>Priority:</strong> {selectedCell.priority}
                </span>
              </div>
              <div className="space-y-1">
                <Label htmlFor="sla-response">Response Hours</Label>
                <Input
                  id="sla-response"
                  type="number"
                  min="1"
                  value={form.responseHours}
                  onChange={(e) =>
                    setForm({ ...form, responseHours: e.target.value })
                  }
                  required
                />
              </div>
              <div className="space-y-1">
                <Label htmlFor="sla-resolution">Resolution Hours</Label>
                <Input
                  id="sla-resolution"
                  type="number"
                  min="1"
                  value={form.resolutionHours}
                  onChange={(e) =>
                    setForm({ ...form, resolutionHours: e.target.value })
                  }
                  required
                />
              </div>
              {saveMutation.isError && (
                <Alert variant="destructive">
                  <AlertDescription>Failed to save SLA policy.</AlertDescription>
                </Alert>
              )}
            </form>
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}
