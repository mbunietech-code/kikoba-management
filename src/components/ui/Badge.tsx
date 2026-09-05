import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

export type Tone = 'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple'

const tones: Record<Tone, string> = {
  neutral: 'bg-neutral-100 text-neutral-600 ring-neutral-200',
  primary: 'bg-primary-50 text-primary-700 ring-primary-100',
  success: 'bg-tertiary-50 text-tertiary-700 ring-tertiary-100',
  warning: 'bg-amber-50 text-amber-700 ring-amber-100',
  danger: 'bg-red-50 text-red-700 ring-red-100',
  info: 'bg-secondary-50 text-secondary-700 ring-secondary-100',
  purple: 'bg-violet-50 text-violet-700 ring-violet-100',
}

export function Badge({
  children,
  tone = 'neutral',
  dot,
  className,
}: {
  children: ReactNode
  tone?: Tone
  dot?: boolean
  className?: string
}) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
        tones[tone],
        className,
      )}
    >
      {dot && <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />}
      {children}
    </span>
  )
}

/* ---- shared status → tone maps ---- */
export const statusTone: Record<string, Tone> = {
  active: 'success', successful: 'success', paid: 'success', approved: 'success', completed: 'success',
  confirmed: 'success', distributed: 'success', verified: 'success', released: 'success',
  pending: 'warning', submitted: 'warning', under_review: 'info', partial: 'warning', calculated: 'info',
  draft: 'neutral', inactive: 'neutral', dormant: 'neutral', closed: 'neutral', cancelled: 'neutral',
  disbursed: 'info', suspended: 'warning', expired: 'neutral',
  overdue: 'danger', rejected: 'danger', failed: 'danger', defaulted: 'danger', reversed: 'danger',
  deceased: 'neutral',
}

export function StatusBadge({ status, label }: { status: string; label?: string }) {
  return (
    <Badge tone={statusTone[status] ?? 'neutral'} dot>
      {label ?? status.replace(/_/g, ' ')}
    </Badge>
  )
}
