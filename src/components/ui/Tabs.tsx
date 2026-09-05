import type { ReactNode } from 'react'
import { motion } from 'motion/react'
import { cn } from '@/lib/cn'

export interface TabItem {
  key: string
  label: ReactNode
  count?: number
}

export function Tabs({
  items,
  value,
  onChange,
  className,
}: {
  items: TabItem[]
  value: string
  onChange: (key: string) => void
  className?: string
}) {
  return (
    <div className={cn('flex gap-1 overflow-x-auto border-b border-neutral-200', className)}>
      {items.map((item) => {
        const active = item.key === value
        return (
          <button
            key={item.key}
            onClick={() => onChange(item.key)}
            className={cn(
              'relative whitespace-nowrap px-3.5 py-2.5 text-sm font-medium transition-colors',
              active ? 'text-primary-700' : 'text-neutral-500 hover:text-neutral-800',
            )}
          >
            <span className="inline-flex items-center gap-1.5">
              {item.label}
              {item.count != null && (
                <span
                  className={cn(
                    'rounded-full px-1.5 py-0.5 text-[11px] font-semibold',
                    active ? 'bg-primary-100 text-primary-700' : 'bg-neutral-100 text-neutral-500',
                  )}
                >
                  {item.count}
                </span>
              )}
            </span>
            {active && (
              <motion.span
                layoutId="tab-underline"
                className="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-primary-600"
                transition={{ type: 'spring', stiffness: 400, damping: 32 }}
              />
            )}
          </button>
        )
      })}
    </div>
  )
}
