/** Formatting helpers — currency is TZS by default (configurable via Settings later). */

let currencyCode = 'TZS'
export function setCurrency(code: string) {
  currencyCode = code
}

export function formatMoney(value: number, opts: { compact?: boolean; withSymbol?: boolean } = {}) {
  const { compact = false, withSymbol = true } = opts
  const nf = new Intl.NumberFormat('en-US', {
    notation: compact ? 'compact' : 'standard',
    maximumFractionDigits: compact ? 1 : 0,
  })
  const num = nf.format(value ?? 0)
  return withSymbol ? `${currencyCode} ${num}` : num
}

export function formatNumber(value: number, maximumFractionDigits = 0) {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits }).format(value ?? 0)
}

export function formatPercent(value: number, digits = 1) {
  return `${new Intl.NumberFormat('en-US', { maximumFractionDigits: digits }).format(value)}%`
}

export function formatDate(input: string | Date, style: 'short' | 'medium' | 'long' = 'medium') {
  const d = typeof input === 'string' ? new Date(input) : input
  if (Number.isNaN(d.getTime())) return '—'
  const map: Record<typeof style, Intl.DateTimeFormatOptions> = {
    short: { day: '2-digit', month: '2-digit', year: 'numeric' },
    medium: { day: '2-digit', month: 'short', year: 'numeric' },
    long: { day: '2-digit', month: 'long', year: 'numeric', weekday: 'long' },
  }
  return new Intl.DateTimeFormat('en-GB', map[style]).format(d)
}

export function formatDateTime(input: string | Date) {
  const d = typeof input === 'string' ? new Date(input) : input
  if (Number.isNaN(d.getTime())) return '—'
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  }).format(d)
}

export function relativeTime(input: string | Date) {
  const d = typeof input === 'string' ? new Date(input) : input
  const diff = d.getTime() - Date.now()
  const abs = Math.abs(diff)
  const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
  const mins = 60_000, hours = 3_600_000, days = 86_400_000
  if (abs < hours) return rtf.format(Math.round(diff / mins), 'minute')
  if (abs < days) return rtf.format(Math.round(diff / hours), 'hour')
  if (abs < 30 * days) return rtf.format(Math.round(diff / days), 'day')
  return formatDate(d, 'medium')
}

export function initials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join('')
}
