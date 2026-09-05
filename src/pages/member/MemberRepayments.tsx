import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import { Badge, Card, CardHeader, DataTable, PageHeader, StatCard } from '@/components/ui'
import type { Column } from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { loanRepayments, loans, repaymentSchedules } from '@/mock/data'
import { sum } from '@/mock/selectors'
import { useMemberId } from './useMember'
import type { LoanRepayment, RepaymentScheduleRow } from '@/types'

export default function MemberRepayments() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const memberId = useMemberId()

  const myLoanIds = new Set(loans.filter((l) => l.memberId === memberId).map((l) => l.id))
  const history = loanRepayments.filter((r) => myLoanIds.has(r.loanId)).sort((a, b) => +new Date(b.date) - +new Date(a.date))
  const upcoming = repaymentSchedules
    .filter((r) => myLoanIds.has(r.loanId) && (r.status === 'pending' || r.status === 'overdue' || r.status === 'partial'))
    .sort((a, b) => +new Date(a.dueDate) - +new Date(b.dueDate))

  const totalPaid = sum(history.map((r) => r.totalPaid))
  const totalDueSoon = sum(upcoming.map((r) => r.totalDue - r.amountPaid))

  const loanNo = (loanId: string) => loans.find((l) => l.id === loanId)?.loanNumber ?? loanId

  const upcomingCols: Column<RepaymentScheduleRow>[] = [
    { key: 'loan', header: t('loans.loanNumber'), render: (r) => <span className="font-medium text-primary-700">{loanNo(r.loanId)}</span> },
    { key: 'n', header: t('loans.installment'), render: (r) => `#${r.installment}` },
    { key: 'due', header: t('loans.dueDate'), sortValue: (r) => r.dueDate, render: (r) => formatDate(r.dueDate) },
    { key: 'amount', header: t('loans.amountDue'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalDue - r.amountPaid)}</span> },
    { key: 'status', header: t('common.status'), render: (r) => <Badge tone={r.status === 'overdue' ? 'danger' : r.status === 'partial' ? 'warning' : 'neutral'} dot>{t(`loans.scheduleStatus.${r.status}`)}</Badge> },
  ]
  const historyCols: Column<LoanRepayment>[] = [
    { key: 'date', header: t('common.date'), sortValue: (r) => r.date, render: (r) => formatDate(r.date) },
    { key: 'loan', header: t('loans.loanNumber'), render: (r) => loanNo(r.loanId) },
    { key: 'ref', header: t('common.reference'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (r) => formatMoney(r.principalPaid) },
    { key: 'interest', header: t('loans.interest'), align: 'right', render: (r) => formatMoney(r.interestPaid) },
    { key: 'total', header: t('common.total'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalPaid)}</span> },
    { key: 'method', header: t('payments.method'), render: (r) => <Badge tone="info">{t(`payments.methods.${r.method}`)}</Badge> },
  ]

  return (
    <>
      <PageHeader title={t('nav.repayments')} subtitle={t('loans.repaymentHistory')} />

      <div className="grid gap-4 sm:grid-cols-2">
        <StatCard index={0} tone="tertiary" label={t('loans.repaymentHistory')} value={formatMoney(totalPaid, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('dashboard.upcomingRepayments')} value={formatMoney(totalDueSoon, { compact: true })} />
      </div>

      <Card className="mt-4">
        <CardHeader title={t('dashboard.upcomingRepayments')} />
        <DataTable columns={upcomingCols} rows={upcoming} rowKey={(r) => r.id} onRowClick={(r) => navigate(`/member/loans/${r.loanId}`)} empty={{ title: t('common.noData'), hint: t('loans.repaymentSchedule') }} />
      </Card>

      <Card className="mt-4">
        <CardHeader title={t('loans.repaymentHistory')} />
        <DataTable columns={historyCols} rows={history} rowKey={(r) => r.id} empty={{ title: t('common.noData') }} />
      </Card>
    </>
  )
}
