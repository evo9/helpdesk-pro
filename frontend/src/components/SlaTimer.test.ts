import { describe, it, expect } from 'vitest'
import { formatDelta } from './SlaTimer'

describe('formatDelta', () => {
  it('formats hours and minutes when ≥1h', () => {
    expect(formatDelta(3_661_000)).toBe('1h 1m')
  })

  it('formats minutes and seconds when 1-59m', () => {
    expect(formatDelta(65_000)).toBe('1m 5s')
  })

  it('formats seconds only when <1m', () => {
    expect(formatDelta(45_000)).toBe('45s')
  })

  it('returns 0s at exact deadline', () => {
    expect(formatDelta(0)).toBe('0s')
  })

  it('prefixes + when overdue', () => {
    expect(formatDelta(-75_000)).toBe('+1m 15s')
  })

  it('prefixes + with hours when long overdue', () => {
    expect(formatDelta(-3_661_000)).toBe('+1h 1m')
  })
})
