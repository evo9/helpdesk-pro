import { useQuery } from '@tanstack/react-query'
import {
  getDashboardSummary,
  getDashboardAgents,
  getTicketsByCategory,
} from '@/api/dashboard'
import type { DashboardAgent, TicketsByCategoryItem } from '@/api/types'
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts'
import { Alert, AlertDescription } from '@/components/ui/alert'

export default function DashboardPage() {
  const summaryQuery = useQuery({
    queryKey: ['dashboard-summary'],
    queryFn: getDashboardSummary,
  })
  const agentsQuery = useQuery({
    queryKey: ['dashboard-agents'],
    queryFn: getDashboardAgents,
  })
  const categoryQuery = useQuery({
    queryKey: ['dashboard-tickets-by-category'],
    queryFn: getTicketsByCategory,
  })

  const summary = summaryQuery.data
  const agents: DashboardAgent[] = agentsQuery.data ?? []
  const categoryData: TicketsByCategoryItem[] = categoryQuery.data ?? []

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold">Dashboard</h1>

      {/* Summary cards */}
      <div className="grid grid-cols-3 gap-4">
        <SummaryCard
          label="Open"
          value={summary?.statuses?.open ?? 0}
          loading={summaryQuery.isLoading}
        />
        <SummaryCard
          label="In Progress"
          value={summary?.statuses?.in_progress ?? 0}
          loading={summaryQuery.isLoading}
        />
        <SummaryCard
          label="SLA Breaches Today"
          value={summary?.slaBreachedToday ?? 0}
          loading={summaryQuery.isLoading}
          highlight={!!summary && summary.slaBreachedToday > 0}
        />
      </div>

      {/* Agent workload */}
      <div>
        <h2 className="text-base font-medium mb-2">Agent Workload</h2>
        {agentsQuery.error ? (
          <Alert variant="destructive">
            <AlertDescription>Failed to load agent workload.</AlertDescription>
          </Alert>
        ) : agentsQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">Loading…</p>
        ) : (
          <div className="overflow-hidden rounded-lg border">
            <table className="w-full text-sm">
              <thead className="bg-muted text-muted-foreground">
                <tr>
                  <th className="px-4 py-2 text-left font-medium">Agent</th>
                  <th className="px-4 py-2 text-left font-medium">Active</th>
                  <th className="px-4 py-2 text-left font-medium">Resolved (30d)</th>
                </tr>
              </thead>
              <tbody>
                {agents.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-4 py-6 text-center text-muted-foreground">
                      No agents.
                    </td>
                  </tr>
                ) : (
                  agents.map((a) => (
                    <tr key={a.agentId} className="border-t">
                      <td className="px-4 py-2">{a.name}</td>
                      <td className="px-4 py-2">{a.activeTickets}</td>
                      <td className="px-4 py-2">{a.resolvedLast30d}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Tickets by category chart */}
      <div>
        <h2 className="text-base font-medium mb-2">
          Tickets by Category (last 30 days)
        </h2>
        {categoryQuery.error ? (
          <Alert variant="destructive">
            <AlertDescription>Failed to load chart data.</AlertDescription>
          </Alert>
        ) : categoryQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">Loading…</p>
        ) : (
          <div className="rounded-lg border p-4">
            <ResponsiveContainer width="100%" height={280}>
              <BarChart
                data={categoryData}
                margin={{ top: 4, right: 8, left: -16, bottom: 0 }}
              >
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="categoryName" tick={{ fontSize: 12 }} />
                <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                <Tooltip />
                <Bar dataKey="count" fill="#6366f1" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}
      </div>
    </div>
  )
}

function SummaryCard({
  label,
  value,
  loading,
  highlight,
}: {
  label: string
  value: number
  loading: boolean
  highlight?: boolean
}) {
  return (
    <div
      className={`rounded-lg border p-4 ${highlight ? 'border-destructive' : ''}`}
    >
      <p className="text-sm text-muted-foreground">{label}</p>
      {loading ? (
        <p className="text-2xl font-bold text-muted-foreground">—</p>
      ) : (
        <p className={`text-2xl font-bold ${highlight ? 'text-destructive' : ''}`}>
          {value}
        </p>
      )}
    </div>
  )
}
