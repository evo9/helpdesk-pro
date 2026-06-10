import { Badge } from '@/components/ui/badge'
import type { SlaStatus } from '@/api/types'

interface SlaIndicatorProps {
  responseSlaStatus: SlaStatus | null
  resolutionSlaStatus: SlaStatus | null
}

const STATUS_ORDER: Record<SlaStatus, number> = { ok: 0, warning: 1, breached: 2 }

export function computeWorstStatus(
  a: SlaStatus | null,
  b: SlaStatus | null
): SlaStatus | null {
  if (a === null && b === null) return null
  if (a === null) return b
  if (b === null) return a
  return STATUS_ORDER[a] >= STATUS_ORDER[b] ? a : b
}

const BADGE_CONFIG = {
  ok: { label: 'SLA OK', variant: 'success' },
  warning: { label: 'SLA Warning', variant: 'warning' },
  breached: { label: 'SLA Breached', variant: 'destructive' },
} as const

export default function SlaIndicator({
  responseSlaStatus,
  resolutionSlaStatus,
}: SlaIndicatorProps) {
  const status = computeWorstStatus(responseSlaStatus, resolutionSlaStatus)
  if (status === null) return null

  const { label, variant } = BADGE_CONFIG[status]
  return <Badge variant={variant}>{label}</Badge>
}
