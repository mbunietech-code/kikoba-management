import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ShieldCheck } from 'lucide-react'
import {
  Button, Card, DataTable, PageHeader, Progress, SkeletonTable, StatCard, StatusBadge, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatMoney } from '@/lib/format'
import { guarantors, memberById } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { Guarantor } from '@/types'

export default function GuarantorsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { data, loading } = useMockQuery(() => guarantors, [])
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')

  const rows = useMemo(
    () =>
      (data ?? []).filter((g) => {
        if (status !== 'all' && g.status !== status) return false
        const names = `${memberById(g.borrowerId)?.fullName} ${memberById(g.guarantorId)?.fullName} ${g.loanNumber}`
        return names.toLowerCase().includes(search.toLowerCase())
      }),
    [data, search, status],
  )

  const CAP = 5_000_000
  const exposureByGuarantor = (gid: string) =>
    sum(guarantors.filter((g) => g.guarantorId === gid && g.status === 'approved').map((g) => g.guaranteedAmount))

  const pending = guarantors.filter((g) => g.status === 'pending').length
  const totalGuaranteed = sum(guarantors.filter((g) => g.status === 'approved').map((g) => g.guaranteedAmount))

  const columns: Column<Guarantor>[] = [
    { key: 'loan', header: t('loans.loanNumber'), render: (g) => <span className="font-medium text-primary-700">{g.loanNumber}</span> },
    { key: 'borrower', header: t('guarantors.borrower'), render: (g) => <MemberCell memberId={g.borrowerId} /> },
    { key: 'guarantor', header: t('guarantors.guarantor'), render: (g) => <MemberCell memberId={g.guarantorId} /> },
    { key: 'amount', header: t('guarantors.guaranteedAmount'), align: 'right', sortValue: (g) => g.guaranteedAmount, render: (g) => formatMoney(g.guaranteedAmount) },
    {
      key: 'exposure',
      header: t('guarantors.exposure'),
      render: (g) => {
        const exp = exposureByGuarantor(g.guarantorId)
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
      key: 'action',
      header: '',
      align: 'right',
      render: (g) =>
        g.status === 'pending' ? (
          <Button size="sm" onClick={() => toast(t('guarantors.verify') + ' ✓')}>{t('guarantors.verify')}</Button>
        ) : null,
    },
  ]

  return (
    <>
      <PageHeader title={t('guarantors.title')} subtitle={t('guarantors.subtitle')} />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('guarantors.title')} value={guarantors.length} icon={<ShieldCheck className="h-4 w-4" />} />
        <StatCard index={1} tone="neutral" label={t('guarantors.status.pending')} value={pending} />
        <StatCard index={2} tone="secondary" label={t('guarantors.guaranteedAmount')} value={formatMoney(totalGuaranteed, { compact: true })} />
      </div>

      <div className="mt-4">
        <ListToolbar
          search={search}
          onSearch={setSearch}
          filters={[
            {
              value: status,
              onChange: setStatus,
              options: [
                { value: 'all', label: t('common.all') },
                ...(['pending', 'approved', 'rejected', 'released'] as const).map((s) => ({ value: s, label: t(`guarantors.status.${s}`) })),
              ],
            },
          ]}
        />
        <Card>
          {loading ? <SkeletonTable /> : <DataTable columns={columns} rows={rows} rowKey={(g) => g.id} onRowClick={(g) => navigate(`/admin/loans/${g.loanId}`)} />}
        </Card>
      </div>
    </>
  )
}
