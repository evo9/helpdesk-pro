import { useState, useEffect, useMemo } from 'react'
import type { SlaStatus } from '@/api/types'

interface SlaTimerProps {
  dueAt: string | null
  slaStatus: SlaStatus | null
}

export function formatDelta(ms: number): string {
  const overdue = ms < 0
  const prefix = overdue ? '+' : ''
  const abs = Math.abs(ms)
  const totalSeconds = Math.floor(abs / 1000)
  const h = Math.floor(totalSeconds / 3600)
  const m = Math.floor((totalSeconds % 3600) / 60)
  const s = totalSeconds % 60
  if (h > 0) return `${prefix}${h}h ${m}m`
  if (m > 0) return `${prefix}${m}m ${s}s`
  return `${prefix}${s}s`
}

const STATUS_CLASS: Record<SlaStatus, string> = {
  ok: 'text-green-600',
  warning: 'text-yellow-600',
  breached: 'text-red-600 font-medium',
}

export default function SlaTimer({ dueAt, slaStatus }: SlaTimerProps) {
  const [now, setNow] = useState(Date.now())

  const dueMs = useMemo(() => (dueAt ? new Date(dueAt).getTime() : 0), [dueAt])

  useEffect(() => {
    if (!dueAt || !slaStatus) return
    const id = setInterval(() => setNow(Date.now()), 1000)
    return () => clearInterval(id)
  }, [dueAt, slaStatus])

  if (!dueAt || !slaStatus) return null

  const delta = dueMs - now
  return (
    <span className={`text-sm tabular-nums ${STATUS_CLASS[slaStatus]}`}>
      {formatDelta(delta)}
    </span>
  )
}
