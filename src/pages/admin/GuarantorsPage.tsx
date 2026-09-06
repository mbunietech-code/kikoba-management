import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ShieldCheck } from 'lucide-react'
import {
  Avatar, Button, Card, DataTable, PageHeader, Progress, StatCard, StatusBadge, useToast, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { RowActions } from '@/components/RowActions'
import { useApiQuery } from '@/lib/useApi'
import { listGuarantors, releaseGuarantor, verifyGuarantor } from '@/api'
import { formatMoney } from '@/lib/format'

const CAP = 5_000_000

export default function GuarantorsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const [status] = useState('all')
  const query = useMemo(() => ({ status: status === 'all' ? undefined : status, per_page: 100 }), [status])
  const { data, loading, error, refetch } = useApiQuery(() => listGuarantors(query), [query])
  const rows: any[] = data?.data ?? []

  async function act(fn: () => Promise<unknown>, msg: string) {
    try {
      await fn()
      toast(msg)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  const exposureByGuarantor = (gid: string) =>
    rows.filter((g) => g.guarantor?.id === gid && g.status === 'approved').reduce((a, g) => a + g.guaranteedAmount, 0)

  const pending = rows.filter((g) => g.status === 'pending').length
  const totalGuaranteed = rows.filter((g) => g.status === 'approved').reduce((a, g) => a + g.guaranteedAmount, 0)

  const m = (member: any) => member ? (
    <span className="flex items-center gap-2"><Avatar name={member.fullName} color={member.avatarColor} size="xs" /><span className="text-[13px]">{member.fullName}</span></span>
  ) : '—'

  const columns: Column<any>[] = [
    { key: 'loan', header: t('loans.loanNumber'), render: (g) => <span className="font-medium text-primary-700">{g.loanNumber}</span> },
    { key: 'borrower', header: t('guarantors.borrower'), render: (g) => m(g.borrower) },
    { key: 'guarantor', header: t('guarantors.guarantor'), render: (g) => m(g.guarantor) },
    { key: 'amount', header: t('guarantors.guaranteedAmount'), align: 'right', sortValue: (g) => g.guaranteedAmount, render: (g) => formatMoney(g.guaranteedAmount) },
    {
      key: 'exposure', header: t('guarantors.exposure'),
      render: (g) => {
        const exp = exposureByGuarantor(g.guarantor?.id)
        return (
          <div className="w-32">
            <Progress value={(exp / CAP) * 100} tone={exp > CAP ? 'danger' : 'primary'} showLabel />
            <p className="mt-0.5 text-[11px] text-neutral-400">{formatMoney(exp, { compact: true })} / {formatMoney(CAP, { compact: true })}</p>
          </div>
        )
      },
    },
    { key: 'status', header: t('common.status'), render: (g) => <StatusBadge status={g.status} label={t(`guarantors.status.${g.status}`)} /> },
    {
      key: 'actions', header: '', align: 'right',
      render: (g) => (
        <span className="flex items-center justify-end gap-1" onClick={(e) => e.stopPropagation()}>
          {g.status === 'pending' && <Button size="sm" onClick={() => act(() => verifyGuarantor(g.id), t('guarantors.verify') + ' ✓')}>{t('guarantors.verify')}</Button>}
          <RowActions
            onDelete={g.status !== 'released' ? () => act(() => releaseGuarantor(g.id), t('guarantors.status.released')) : undefined}
            deleteLabel={t('guarantors.status.released')}
            deleteMessage={t('common.confirmDelete')}
          />
        </span>
      ),
    },
  ]

  return (
    <>
      <PageHeader title={t('guarantors.title')} subtitle={t('guarantors.subtitle')} />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('guarantors.title')} value={rows.length} icon={<ShieldCheck className="h-4 w-4" />} />
        <StatCard index={1} tone="neutral" label={t('guarantors.status.pending')} value={pending} />
        <StatCard index={2} tone="secondary" label={t('guarantors.guaranteedAmount')} value={formatMoney(totalGuaranteed, { compact: true })} />
      </div>

      <Card className="mt-4">
        {error ? (
          <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
        ) : loading ? (
          <EmptyState title={t('common.loading')} />
        ) : (
          <DataTable columns={columns} rows={rows} rowKey={(g) => g.id} onRowClick={(g) => navigate(`/admin/loans/${g.loanId}`)} />
        )}
      </Card>
    </>
  )
}
