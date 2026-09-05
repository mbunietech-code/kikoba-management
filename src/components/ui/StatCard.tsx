import type { ReactNode } from 'react'
import { motion } from 'motion/react'
import { ArrowDownRight, ArrowUpRight } from 'lucide-react'
import { cn } from '@/lib/cn'

interface StatCardProps {
  label: string
  value: ReactNode
  icon?: ReactNode
  delta?: { value: string; direction: 'up' | 'down'; good?: boolean }
  hint?: string
  tone?: 'primary' | 'secondary' | 'tertiary' | 'neutral'
  index?: number
}

const toneRing: Record<NonNullable<StatCardProps['tone']>, string> = {
  primary: 'bg-primary-50 text-primary-700',
  secondary: 'bg-secondary-50 text-secondary-700',
  tertiary: 'bg-tertiary-50 text-tertiary-700',
  neutral: 'bg-neutral-100 text-neutral-600',
}

export function StatCard({ label, value, icon, delta, hint, tone = 'primary', index = 0 }: StatCardProps) {
  const deltaGood = delta?.good ?? delta?.direction === 'up'
  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.35, delay: index * 0.05, ease: [0.16, 1, 0.3, 1] }}
      className="card p-5"
    >
      <div className="flex items-start justify-between">
        <p className="text-[13px] font-medium text-neutral-500">{label}</p>
        {icon && <span className={cn('flex h-9 w-9 items-center justify-center rounded-xl', toneRing[tone])}>{icon}</span>}
      </div>
      <p className="mt-3 font-display text-2xl font-bold tracking-tight text-neutral-900">{value}</p>
      <div className="mt-2 flex items-center gap-2 text-[13px]">
        {delta && (
          <span className={cn('inline-flex items-center gap-0.5 font-medium', deltaGood ? 'text-tertiary-600' : 'text-danger')}>
            {delta.direction === 'up' ? <ArrowUpRight className="h-3.5 w-3.5" /> : <ArrowDownRight className="h-3.5 w-3.5" />}
            {delta.value}
          </span>
        )}
        {hint && <span className="text-neutral-400">{hint}</span>}
      </div>
    </motion.div>
  )
}
