import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

export function DescriptionList({
  items,
  columns = 2,
  className,
}: {
  items: { label: ReactNode; value: ReactNode }[]
  columns?: 1 | 2 | 3
  className?: string
}) {
  const cols = { 1: 'sm:grid-cols-1', 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-3' }[columns]
  return (
    <dl className={cn('grid grid-cols-1 gap-x-6 gap-y-4', cols, className)}>
      {items.map((item, i) => (
        <div key={i} className="min-w-0">
          <dt className="text-[12px] font-medium uppercase tracking-wide text-neutral-400">{item.label}</dt>
          <dd className="mt-1 text-sm text-neutral-800">{item.value}</dd>
        </div>
      ))}
    </dl>
  )
}
