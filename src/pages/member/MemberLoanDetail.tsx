import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, DataTable, DescriptionList, PageHeader, Progress,
  StatCard, StatusBadge, Tabs, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { formatDate, formatMoney } from '@/lib/format'
import { guarantors, loanRepayments, loans, memberById, repaymentSchedules } from '@/mock/data'
import { useMemberId } from './useMember'
import type { LoanRepayment, RepaymentScheduleRow } from '@/types'

export default function MemberLoanDetail() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { id = '' } = useParams()
  const memberId = useMemberId()
  const loan = loans.find((l) => l.id === id && l.memberId === memberId)
  const [tab, setTab] = useState('schedule')

  if (!loan) return <NotFoundInline />

  const schedule = repaymentSchedules.filter((r) => r.loanId === id)
  const repayments = loanRepayments.filter((r) => r.loanId === id)
  const loanGuarantors = guarantors.filter((g) => g.loanId === id)

  const scheduleCols: Column<RepaymentScheduleRow>[] = [
    { key: 'n', header: '#', render: (r) => r.installment },
    { key: 'due', header: t('loans.dueDate'), render: (r) => formatDate(r.dueDate) },
    { key: 'total', header: t('loans.amountDue'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalDue)}</span> },
    { key: 'paid', header: t('loans.amountPaid'), align: 'right', render: (r) => formatMoney(r.amountPaid) },
    { key: 'status', header: t('common.status'), render: (r) => <StatusBadge status={r.status} label={t(`loans.scheduleStatus.${r.status}`)} /> },
  ]
  const repayCols: Column<LoanRepayment>[] = [
    { key: 'date', header: t('common.date'), sortValue: (r) => r.date, render: (r) => formatDate(r.date) },
    { key: 'ref', header: t('common.reference'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'total', header: t('common.amount'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalPaid)}</span> },
    { key: 'method', header: t('payments.method'), render: (r) => <Badge tone="info">{t(`payments.methods.${r.method}`)}</Badge> },
  ]

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/member/loans')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('nav.myLoans')}
          </button>
        }
        title={loan.loanNumber}
        subtitle={loan.productName}
        actions={
          ['active', 'overdue', 'disbursed'].includes(loan.status) ? (
            <Button onClick={() => toast(t('loans.recordRepayment') + ' ✓')}>{t('loans.recordRepayment')}</Button>
          ) : null
        }
      />

      <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div className="flex flex-col gap-4">
          <StatCard index={0} label={t('member.outstanding')} value={formatMoney(loan.outstanding)} />
          <Card>
            <CardBody>
              <Progress value={loan.total ? (loan.amountPaid / loan.total) * 100 : 0} showLabel tone={loan.status === 'overdue' ? 'danger' : 'tertiary'} />
              <p className="mt-2 text-[12px] text-neutral-500">{formatMoney(loan.amountPaid)} / {formatMoney(loan.total)}</p>
            </CardBody>
          </Card>
          <Card>
            <CardHeader title={t('common.summary')} className="!py-3" />
            <CardBody>
              <DescriptionList
                columns={1}
                items={[
                  { label: t('loans.principal'), value: formatMoney(loan.principal) },
                  { label: t('loans.interest'), value: formatMoney(loan.interest) },
                  { label: t('loans.fees'), value: formatMoney(loan.fees + loan.insurance) },
                  { label: t('loans.totalRepayable'), value: <span className="font-semibold">{formatMoney(loan.total)}</span> },
                  { label: t('loans.disbursementDate'), value: loan.disbursementDate ? formatDate(loan.disbursementDate) : '—' },
                  { label: t('loans.maturityDate'), value: loan.maturityDate ? formatDate(loan.maturityDate) : '—' },
                  { label: t('common.status'), value: <StatusBadge status={loan.status} label={t(`loans.status.${loan.status}`)} /> },
                ]}
              />
            </CardBody>
          </Card>
        </div>

        <Card className="min-w-0">
          <div className="px-3 pt-2">
            <Tabs
              value={tab}
              onChange={setTab}
              items={[
                { key: 'schedule', label: t('loans.tabs.schedule'), count: schedule.length },
                { key: 'repayments', label: t('loans.tabs.repayments'), count: repayments.length },
                { key: 'guarantors', label: t('loans.tabs.guarantors'), count: loanGuarantors.length },
              ]}
            />
          </div>
          {tab === 'schedule' && <DataTable columns={scheduleCols} rows={schedule} rowKey={(r) => r.id} pageSize={24} empty={{ title: t('common.noData') }} />}
          {tab === 'repayments' && <DataTable columns={repayCols} rows={repayments} rowKey={(r) => r.id} empty={{ title: t('common.noData') }} />}
          {tab === 'guarantors' && (
            <DataTable
              columns={[
                { key: 'g', header: t('guarantors.guarantor'), render: (g) => memberById(g.guarantorId)?.fullName ?? '—' },
                { key: 'amt', header: t('guarantors.guaranteedAmount'), align: 'right', render: (g) => formatMoney(g.guaranteedAmount) },
                { key: 'status', header: t('common.status'), render: (g) => <StatusBadge status={g.status} label={t(`guarantors.status.${g.status}`)} /> },
              ]}
              rows={loanGuarantors}
              rowKey={(g) => g.id}
              empty={{ title: t('common.noData') }}
            />
          )}
        </Card>
      </div>
    </>
  )
}
