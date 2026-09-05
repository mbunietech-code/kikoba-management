import { useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { motion } from 'motion/react'
import { cn } from '@/lib/cn'
import { useTranslation } from 'react-i18next'
import { EmptyState } from './EmptyState'

export interface Column<T> {
  key: string
  header: ReactNode
  render: (row: T) => ReactNode
  sortValue?: (row: T) => string | number
  align?: 'left' | 'right' | 'center'
  className?: string
}

interface DataTableProps<T> {
  columns: Column<T>[]
  rows: T[]
  rowKey: (row: T) => string
  onRowClick?: (row: T) => void
  pageSize?: number
  empty?: { title: string; hint?: string }
  dense?: boolean
}

export function DataTable<T>({
  columns,
  rows,
  rowKey,
  onRowClick,
  pageSize = 12,
  empty,
  dense,
}: DataTableProps<T>) {
  const { t } = useTranslation()
  const [sort, setSort] = useState<{ key: string; dir: 'asc' | 'desc' } | null>(null)
  const [page, setPage] = useState(1)

  const sorted = useMemo(() => {
    if (!sort) return rows
    const col = columns.find((c) => c.key === sort.key)
    if (!col?.sortValue) return rows
    const s = [...rows].sort((a, b) => {
      const av = col.sortValue!(a)
      const bv = col.sortValue!(b)
      if (av < bv) return sort.dir === 'asc' ? -1 : 1
      if (av > bv) return sort.dir === 'asc' ? 1 : -1
      return 0
    })
    return s
  }, [rows, sort, columns])

  const totalPages = Math.max(1, Math.ceil(sorted.length / pageSize))
  const current = Math.min(page, totalPages)
  const pageRows = sorted.slice((current - 1) * pageSize, current * pageSize)

  function toggleSort(key: string) {
    setSort((prev) =>
      prev?.key === key
        ? prev.dir === 'asc'
          ? { key, dir: 'desc' }
          : null
        : { key, dir: 'asc' },
    )
  }

  if (rows.length === 0) {
    return <EmptyState title={empty?.title ?? t('common.noData')} hint={empty?.hint ?? t('common.noDataHint')} />
  }

  return (
    <div>
      <div className="overflow-x-auto">
        <table className="w-full border-collapse text-sm">
          <thead>
            <tr className="border-b border-neutral-200 text-left">
              {columns.map((c) => (
                <th
                  key={c.key}
                  className={cn(
                    'whitespace-nowrap px-4 py-3 text-[12px] font-semibold uppercase tracking-wide text-neutral-500',
                    c.align === 'right' && 'text-right',
                    c.align === 'center' && 'text-center',
                    c.sortValue && 'cursor-pointer select-none hover:text-neutral-800',
                  )}
                  onClick={() => c.sortValue && toggleSort(c.key)}
                >
                  <span className="inline-flex items-center gap-1">
                    {c.header}
                    {sort?.key === c.key && <span className="text-primary-600">{sort.dir === 'asc' ? '↑' : '↓'}</span>}
                  </span>
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {pageRows.map((row, i) => (
              <motion.tr
                key={rowKey(row)}
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.2, delay: Math.min(i * 0.015, 0.2) }}
                className={cn(
                  'border-b border-neutral-100 last:border-0',
                  onRowClick && 'cursor-pointer hover:bg-neutral-50',
                )}
                onClick={() => onRowClick?.(row)}
              >
                {columns.map((c) => (
                  <td
                    key={c.key}
                    className={cn(
                      'px-4 text-neutral-700',
                      dense ? 'py-2.5' : 'py-3.5',
                      c.align === 'right' && 'text-right tabular-nums',
                      c.align === 'center' && 'text-center',
                      c.className,
                    )}
                  >
                    {c.render(row)}
                  </td>
                ))}
              </motion.tr>
            ))}
          </tbody>
        </table>
      </div>

      {totalPages > 1 && (
        <div className="flex items-center justify-between gap-4 border-t border-neutral-100 px-4 py-3 text-[13px] text-neutral-500">
          <span>
            {t('common.showing')} {(current - 1) * pageSize + 1}–{Math.min(current * pageSize, sorted.length)} {t('common.of')}{' '}
            {sorted.length}
          </span>
          <div className="flex items-center gap-1">
            <button
              className="rounded-lg px-2 py-1 hover:bg-neutral-100 disabled:opacity-40"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={current === 1}
            >
              {t('common.previous')}
            </button>
            <span className="px-2">
              {t('common.page')} {current} / {totalPages}
            </span>
            <button
              className="rounded-lg px-2 py-1 hover:bg-neutral-100 disabled:opacity-40"
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              disabled={current === totalPages}
            >
              {t('common.next')}
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
