import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { RefreshCw } from 'lucide-react'
import {
  Badge, Button, Card, DataTable, PageHeader, SkeletonTable, StatCard, StatusBadge, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDateTime, formatMoney } from '@/lib/format'
import { payments } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { Payment } from '@/types'

export default function PaymentsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { data, loading } = useMockQuery(() => payments, [])
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')
  const [method, setMethod] = useState('all')

  const rows = useMemo(
    () =>
      (data ?? []).filter((p) => {
        if (status !== 'all' && p.status !== status) return false
        if (method !== 'all' && p.method !== method) return false
        return `${p.internalRef} ${p.externalRef} ${p.provider} ${p.purpose}`.toLowerCase().includes(search.toLowerCase())
      }),
    [data, search, status, method],
  )

  const successful = sum(payments.filter((p) => p.status === 'successful').map((p) => p.amount))
  const pending = payments.filter((p) => p.status === 'pending').length
  const failed = payments.filter((p) => p.status === 'failed' || p.status === 'reversed').length

  const columns: Column<Payment>[] = [
    { key: 'ref', header: t('payments.internalRef'), render: (p) => <span className="font-mono text-[12px] text-neutral-500">{p.internalRef}</span> },
    { key: 'member', header: t('common.member'), render: (p) => <MemberCell memberId={p.memberId} /> },
    { key: 'purpose', header: t('common.description'), render: (p) => <span className="text-[13px]">{p.purpose}</span> },
    { key: 'method', header: t('payments.method'), render: (p) => <Badge tone="info">{t(`payments.methods.${p.method}`)}</Badge> },
    { key: 'provider', header: t('payments.provider'), render: (p) => <span className="text-[13px] text-neutral-500">{p.provider}</span> },
    { key: 'amount', header: t('common.amount'), align: 'right', sortValue: (p) => p.amount, render: (p) => <span className="font-medium">{formatMoney(p.amount)}</span> },
    { key: 'date', header: t('payments.paidAt'), sortValue: (p) => p.paidAt, render: (p) => <span className="text-[13px] text-neutral-500">{formatDateTime(p.paidAt)}</span> },
    { key: 'status', header: t('common.status'), render: (p) => <StatusBadge status={p.status} /> },
    {
      key: 'act', header: '', align: 'right',
      render: (p) => (p.status === 'pending' ? <Button size="sm" onClick={() => toast(t('payments.verify') + ' ✓')}>{t('payments.verify')}</Button> : null),
    },
  ]

  return (
    <>
      <PageHeader
        title={t('payments.title')}
        subtitle={t('payments.subtitle')}
        actions={<Button variant="outlined" leftIcon={<RefreshCw className="h-4 w-4" />} onClick={() => toast(t('payments.reconcile') + ' ✓')}>{t('payments.reconcile')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} tone="tertiary" label={t('payments.status.successful')} value={formatMoney(successful, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('payments.status.pending')} value={pending} />
        <StatCard index={2} tone="neutral" label={`${t('payments.status.failed')} / ${t('payments.status.reversed')}`} value={failed} />
      </div>

      <div className="mt-4">
        <ListToolbar
          search={search}
          onSearch={setSearch}
          filters={[
            {
              value: status, onChange: setStatus,
              options: [{ value: 'all', label: t('common.all') }, ...(['pending', 'successful', 'failed', 'reversed'] as const).map((s) => ({ value: s, label: t(`payments.status.${s}`) }))],
            },
            {
              value: method, onChange: setMethod,
              options: [{ value: 'all', label: t('common.all') }, ...(['mobile_money', 'bank', 'card', 'cash', 'manual'] as const).map((m) => ({ value: m, label: t(`payments.methods.${m}`) }))],
            },
          ]}
        />
        <Card>{loading ? <SkeletonTable /> : <DataTable columns={columns} rows={rows} rowKey={(p) => p.id} />}</Card>
      </div>
    </>
  )
}
