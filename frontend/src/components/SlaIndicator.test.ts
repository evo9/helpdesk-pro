import { describe, it, expect } from 'vitest'
import { computeWorstStatus } from './SlaIndicator'

describe('computeWorstStatus', () => {
  it('returns null when both statuses are null', () => {
    expect(computeWorstStatus(null, null)).toBe(null)
  })

  it('returns the non-null status when one side is null', () => {
    expect(computeWorstStatus('ok', null)).toBe('ok')
    expect(computeWorstStatus(null, 'warning')).toBe('warning')
    expect(computeWorstStatus(null, 'breached')).toBe('breached')
  })

  it('returns the worse status when both are present', () => {
    expect(computeWorstStatus('ok', 'warning')).toBe('warning')
    expect(computeWorstStatus('warning', 'ok')).toBe('warning')
    expect(computeWorstStatus('ok', 'breached')).toBe('breached')
    expect(computeWorstStatus('warning', 'breached')).toBe('breached')
    expect(computeWorstStatus('breached', 'ok')).toBe('breached')
  })

  it('returns the same status when both are equal', () => {
    expect(computeWorstStatus('ok', 'ok')).toBe('ok')
    expect(computeWorstStatus('warning', 'warning')).toBe('warning')
    expect(computeWorstStatus('breached', 'breached')).toBe('breached')
  })
})
