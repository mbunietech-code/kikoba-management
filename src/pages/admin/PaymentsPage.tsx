import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { RefreshCw } from 'lucide-react'
import {
  Avatar, Badge, Button, Card, DataTable, PageHeader, StatCard, StatusBadge, useToast, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { RowActions } from '@/components/RowActions'
import { ListToolbar } from '@/components/ListToolbar'
import { useApiQuery } from '@/lib/useApi'
import { listPayments, reversePayment, verifyPayment } from '@/api'
import { formatDateTime, formatMoney } from '@/lib/format'

export default function PaymentsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')
  const [method, setMethod] = useState('all')

  const query = useMemo(() => ({
    search: search || undefined,
    status: status === 'all' ? undefined : status,
    method: method === 'all' ? undefined : method,
    per_page: 100,
  }), [search, status, method])
  const { data, loading, error, refetch } = useApiQuery(() => listPayments(query), [query])
  const rows: any[] = data?.data ?? []

  const successful = rows.filter((p) => p.status === 'successful').reduce((a, p) => a + p.amount, 0)
  const pending = rows.filter((p) => p.status === 'pending').length

  async function act(fn: () => Promise<unknown>, msg: string) {
    try {
      await fn()
      toast(msg)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  const columns: Column<any>[] = [
    { key: 'ref', header: t('payments.internalRef'), render: (p) => <span className="font-mono text-[12px] text-neutral-500">{p.internalRef}</span> },
    { key: 'member', header: t('common.member'), render: (p) => p.member ? <span className="flex items-center gap-2"><Avatar name={p.member.fullName} color={p.member.avatarColor} size="xs" />{p.member.fullName}</span> : '—' },
    { key: 'purpose', header: t('common.description'), render: (p) => <span className="text-[13px]">{p.purpose}</span> },
    { key: 'method', header: t('payments.method'), render: (p) => <Badge tone="info">{t(`payments.methods.${p.method}`)}</Badge> },
    { key: 'amount', header: t('common.amount'), align: 'right', sortValue: (p) => p.amount, render: (p) => <span className="font-medium">{formatMoney(p.amount)}</span> },
    { key: 'date', header: t('payments.paidAt'), sortValue: (p) => p.paidAt, render: (p) => <span className="text-[13px] text-neutral-500">{formatDateTime(p.paidAt)}</span> },
    { key: 'status', header: t('common.status'), render: (p) => <StatusBadge status={p.status} /> },
    {
      key: 'actions', header: '', align: 'right',
      render: (p) => (
        <span className="flex items-center justify-end gap-1" onClick={(e) => e.stopPropagation()}>
          {p.status === 'pending' && <Button size="sm" onClick={() => act(() => verifyPayment(p.id), t('payments.verify') + ' ✓')}>{t('payments.verify')}</Button>}
          {['successful', 'pending'].includes(p.status) && (
            <RowActions onDelete={() => act(() => reversePayment(p.id), t('common.reverse') + ' ✓')} deleteLabel={t('common.reverse')} deleteMessage={t('common.confirmDelete')} />
          )}
        </span>
      ),
    },
  ]

  return (
    <>
      <PageHeader
        title={t('payments.title')}
        subtitle={t('payments.subtitle')}
        actions={<Button variant="outlined" leftIcon={<RefreshCw className="h-4 w-4" />} onClick={() => toast(t('payments.reconcile') + ' ✓')}>{t('payments.reconcile')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-2">
        <StatCard index={0} tone="tertiary" label={t('payments.status.successful')} value={formatMoney(successful, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('payments.status.pending')} value={pending} />
      </div>

      <div className="mt-4">
        <ListToolbar
          search={search}
          onSearch={setSearch}
          filters={[
            { value: status, onChange: setStatus, options: [{ value: 'all', label: t('common.all') }, ...(['pending', 'successful', 'failed', 'reversed'] as const).map((s) => ({ value: s, label: t(`payments.status.${s}`) }))] },
            { value: method, onChange: setMethod, options: [{ value: 'all', label: t('common.all') }, ...(['mobile_money', 'bank', 'card', 'cash', 'manual'] as const).map((m) => ({ value: m, label: t(`payments.methods.${m}`) }))] },
          ]}
        />
        <Card>
          {error ? (
            <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
          ) : loading ? (
            <EmptyState title={t('common.loading')} />
          ) : (
            <DataTable columns={columns} rows={rows} rowKey={(p) => p.id} />
          )}
        </Card>
      </div>
    </>
  )
}
