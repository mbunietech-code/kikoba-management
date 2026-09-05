import { motion } from 'motion/react'
import { cn } from '@/lib/cn'

export function Progress({
  value,
  tone = 'primary',
  className,
  showLabel,
}: {
  value: number
  tone?: 'primary' | 'secondary' | 'tertiary' | 'danger'
  className?: string
  showLabel?: boolean
}) {
  const pct = Math.max(0, Math.min(100, value))
  const bar = {
    primary: 'bg-primary-600',
    secondary: 'bg-secondary-600',
    tertiary: 'bg-tertiary-600',
    danger: 'bg-danger',
  }[tone]
  return (
    <div className={cn('flex items-center gap-2', className)}>
      <div className="h-2 flex-1 overflow-hidden rounded-full bg-neutral-150 bg-neutral-100">
        <motion.div
          className={cn('h-full rounded-full', bar)}
          initial={{ width: 0 }}
          animate={{ width: `${pct}%` }}
          transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
        />
      </div>
      {showLabel && <span className="w-10 text-right text-[12px] font-medium tabular-nums text-neutral-500">{Math.round(pct)}%</span>}
    </div>
  )
}
