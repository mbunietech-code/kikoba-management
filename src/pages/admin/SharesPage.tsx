import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus } from 'lucide-react'
import {
  Avatar, Button, Card, DataTable, PageHeader, SkeletonTable, StatCard, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { useApiQuery } from '@/lib/useApi'
import { listShares, sharesSummary } from '@/api'
import { formatDate, formatMoney, formatNumber } from '@/lib/format'

interface ShareRow {
  id: string
  member?: { fullName: string; memberNumber: string; avatarColor?: string }
  quantity: number
  pricePerShare: number
  totalValue: number
  purchasedAt: string
  transactionRef: string
}

export default function SharesPage() {
  const { t } = useTranslation()
  const [search, setSearch] = useState('')
  const query = useMemo(() => ({ search: search || undefined, per_page: 100 }), [search])
  const { data, loading, error, refetch } = useApiQuery(() => listShares(query), [query])
  const { data: summary } = useApiQuery(() => sharesSummary(), [])
  const rows: ShareRow[] = data?.data ?? []

  const columns: Column<ShareRow>[] = [
    {
      key: 'member', header: t('common.member'),
      render: (s) => s.member ? (
        <span className="flex items-center gap-2">
          <Avatar name={s.member.fullName} color={s.member.avatarColor} size="sm" />
          <span>
            <span className="block text-[13px] font-medium text-neutral-800">{s.member.fullName}</span>
            <span className="block text-[11px] text-neutral-400">{s.member.memberNumber}</span>
          </span>
        </span>
      ) : '—',
    },
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.transactionRef}</span> },
    { key: 'qty', header: t('shares.quantity'), align: 'right', sortValue: (s) => s.quantity, render: (s) => formatNumber(s.quantity) },
    { key: 'price', header: t('shares.pricePerShare'), align: 'right', render: (s) => formatMoney(s.pricePerShare) },
    { key: 'value', header: t('shares.totalValue'), align: 'right', sortValue: (s) => s.totalValue, render: (s) => <span className="font-medium">{formatMoney(s.totalValue)}</span> },
    { key: 'date', header: t('shares.purchasedAt'), sortValue: (s) => s.purchasedAt, render: (s) => <span className="text-[13px] text-neutral-500">{formatDate(s.purchasedAt)}</span> },
  ]

  return (
    <>
      <PageHeader
        title={t('shares.title')}
        subtitle={t('shares.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />}>{t('shares.recordPurchase')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('shares.shareCapital')} value={formatMoney(summary?.shareCapital ?? 0, { compact: true })} />
        <StatCard index={1} tone="tertiary" label={t('shares.sharesOutstanding')} value={formatNumber(summary?.sharesOutstanding ?? 0)} />
        <StatCard index={2} tone="secondary" label={t('shares.holders')} value={summary?.holders ?? 0} />
      </div>

      <div className="mt-4">
        <ListToolbar search={search} onSearch={setSearch} />
        <Card>
          {error ? (
            <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
          ) : loading ? (
            <SkeletonTable />
          ) : (
            <DataTable columns={columns} rows={rows} rowKey={(s) => s.id} />
          )}
        </Card>
      </div>
    </>
  )
}
