import { Badge } from '@/components/ui/badge'
import type { SlaStatus } from '@/api/types'

interface SlaIndicatorProps {
  responseSlaStatus: SlaStatus | null | undefined
  resolutionSlaStatus: SlaStatus | null | undefined
}

const STATUS_ORDER: Record<SlaStatus, number> = { ok: 0, warning: 1, breached: 2 }

export function computeWorstStatus(
  a: SlaStatus | null | undefined,
  b: SlaStatus | null | undefined
): SlaStatus | null {
  // API Platform omits null fields; normalise undefined → null
  const safeA = a ?? null
  const safeB = b ?? null
  if (safeA === null && safeB === null) return null
  if (safeA === null) return safeB
  if (safeB === null) return safeA
  return STATUS_ORDER[safeA] >= STATUS_ORDER[safeB] ? safeA : safeB
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
