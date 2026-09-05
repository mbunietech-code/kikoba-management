import { initials } from '@/lib/format'
import { cn } from '@/lib/cn'

export function Avatar({
  name,
  color,
  size = 'md',
}: {
  name: string
  color?: string
  size?: 'xs' | 'sm' | 'md' | 'lg'
}) {
  const dims = {
    xs: 'h-6 w-6 text-[10px]',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-14 w-14 text-lg',
  }[size]
  return (
    <span
      className={cn('inline-flex shrink-0 items-center justify-center rounded-full font-semibold text-white', dims)}
      style={{ backgroundColor: color ?? '#115e59' }}
    >
      {initials(name)}
    </span>
  )
}
