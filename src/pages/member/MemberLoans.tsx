import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import { Plus } from 'lucide-react'
import {
  Button, Card, CardBody, DataTable, EmptyState, PageHeader, Progress, StatCard, StatusBadge,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { loans } from '@/mock/data'
import { sum } from '@/mock/selectors'
import { useMemberId } from './useMember'
import type { Loan } from '@/types'

export default function MemberLoans() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const memberId = useMemberId()
  const rows = loans.filter((l) => l.memberId === memberId)
  const active = rows.filter((l) => ['active', 'overdue', 'disbursed'].includes(l.status))
  const outstanding = sum(rows.map((l) => l.outstanding))

  const columns: Column<Loan>[] = [
    { key: 'no', header: t('loans.loanNumber'), render: (l) => <span className="font-medium text-primary-700">{l.loanNumber}</span> },
    { key: 'product', header: t('loans.product'), render: (l) => l.productName },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (l) => formatMoney(l.principal) },
    { key: 'progress', header: t('loans.amountPaid'), render: (l) => <div className="w-28"><Progress value={l.total ? (l.amountPaid / l.total) * 100 : 0} showLabel /></div> },
    { key: 'outstanding', header: t('loans.outstanding'), align: 'right', render: (l) => <span className="font-medium">{formatMoney(l.outstanding)}</span> },
    { key: 'date', header: t('loans.applicationDate'), sortValue: (l) => l.applicationDate, render: (l) => formatDate(l.applicationDate) },
    { key: 'status', header: t('common.status'), render: (l) => <StatusBadge status={l.status} label={t(`loans.status.${l.status}`)} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('nav.myLoans')}
        subtitle={t('loans.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => navigate('/member/loans/apply')}>{t('member.applyLoan')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('nav.active')} value={active.length} />
        <StatCard index={1} tone="neutral" label={t('member.outstanding')} value={formatMoney(outstanding, { compact: true })} />
        <StatCard index={2} tone="tertiary" label={t('common.total')} value={rows.length} />
      </div>

      <Card className="mt-4">
        {rows.length === 0 ? (
          <CardBody>
            <EmptyState
              title={t('common.noData')}
              hint={t('loans.subtitle')}
              action={<Button onClick={() => navigate('/member/loans/apply')}>{t('member.applyLoan')}</Button>}
            />
          </CardBody>
        ) : (
          <DataTable columns={columns} rows={rows} rowKey={(l) => l.id} onRowClick={(l) => navigate(`/member/loans/${l.id}`)} />
        )}
      </Card>
    </>
  )
}
