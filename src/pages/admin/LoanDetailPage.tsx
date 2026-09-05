import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Check, X } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, DataTable, DescriptionList, PageHeader, Progress,
  StatCard, StatusBadge, Tabs, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { MemberCell } from '@/components/MemberCell'
import { formatDate, formatMoney } from '@/lib/format'
import { guarantors, loanRepayments, loans, memberById, repaymentSchedules } from '@/mock/data'
import type { Guarantor, LoanRepayment, RepaymentScheduleRow } from '@/types'
import { cn } from '@/lib/cn'

const WORKFLOW = ['submitted', 'under_review', 'approved', 'disbursed', 'active', 'completed'] as const

export default function LoanDetailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { id = '' } = useParams()
  const loan = loans.find((l) => l.id === id)
  const [tab, setTab] = useState('overview')

  if (!loan) return <NotFoundInline />

  const schedule = repaymentSchedules.filter((r) => r.loanId === id)
  const repayments = loanRepayments.filter((r) => r.loanId === id)
  const loanGuarantors = guarantors.filter((g) => g.loanId === id)
  const currentStep = WORKFLOW.indexOf(loan.status as (typeof WORKFLOW)[number])
  const canDecide = ['submitted', 'under_review'].includes(loan.status)

  const scheduleCols: Column<RepaymentScheduleRow>[] = [
    { key: 'n', header: '#', render: (r) => r.installment },
    { key: 'due', header: t('loans.dueDate'), render: (r) => formatDate(r.dueDate) },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (r) => formatMoney(r.principalDue) },
    { key: 'interest', header: t('loans.interest'), align: 'right', render: (r) => formatMoney(r.interestDue) },
    { key: 'total', header: t('loans.amountDue'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalDue)}</span> },
    { key: 'paid', header: t('loans.amountPaid'), align: 'right', render: (r) => formatMoney(r.amountPaid) },
    { key: 'status', header: t('common.status'), render: (r) => <StatusBadge status={r.status} label={t(`loans.scheduleStatus.${r.status}`)} /> },
  ]
  const repayCols: Column<LoanRepayment>[] = [
    { key: 'date', header: t('common.date'), sortValue: (r) => r.date, render: (r) => formatDate(r.date) },
    { key: 'ref', header: t('common.reference'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (r) => formatMoney(r.principalPaid) },
    { key: 'interest', header: t('loans.interest'), align: 'right', render: (r) => formatMoney(r.interestPaid) },
    { key: 'fee', header: t('loans.fees'), align: 'right', render: (r) => formatMoney(r.feePaid) },
    { key: 'total', header: t('common.total'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalPaid)}</span> },
    { key: 'method', header: t('payments.method'), render: (r) => <Badge tone="info">{t(`payments.methods.${r.method}`)}</Badge> },
  ]
  const guarantorCols: Column<Guarantor>[] = [
    { key: 'g', header: t('guarantors.guarantor'), render: (g) => <MemberCell memberId={g.guarantorId} /> },
    { key: 'amt', header: t('guarantors.guaranteedAmount'), align: 'right', render: (g) => formatMoney(g.guaranteedAmount) },
    { key: 'status', header: t('common.status'), render: (g) => <StatusBadge status={g.status} label={t(`guarantors.status.${g.status}`)} /> },
    { key: 'approved', header: t('guarantors.approvedAt'), render: (g) => (g.approvedAt ? formatDate(g.approvedAt) : '—') },
  ]

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/admin/loans')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('loans.title')}
          </button>
        }
        title={loan.loanNumber}
        subtitle={`${loan.productName} · ${memberById(loan.memberId)?.fullName}`}
        actions={
          canDecide ? (
            <>
              <Button variant="danger" leftIcon={<X className="h-4 w-4" />} onClick={() => toast(t('common.reject') + ' ✓', 'error')}>{t('common.reject')}</Button>
              <Button leftIcon={<Check className="h-4 w-4" />} onClick={() => toast(t('common.approve') + ' ✓')}>{t('common.approve')}</Button>
            </>
          ) : loan.status === 'approved' ? (
            <Button onClick={() => toast(t('loans.disburse') + ' ✓')}>{t('loans.disburse')}</Button>
          ) : ['active', 'overdue', 'disbursed'].includes(loan.status) ? (
            <Button onClick={() => toast(t('loans.recordRepayment') + ' ✓')}>{t('loans.recordRepayment')}</Button>
          ) : null
        }
      />

      {/* workflow */}
      <Card className="mb-4">
        <CardBody>
          <div className="flex flex-wrap items-center gap-y-3">
            {WORKFLOW.map((step, i) => {
              const done = currentStep >= 0 && i <= currentStep
              const activeStep = i === currentStep
              return (
                <div key={step} className="flex items-center">
                  <div className="flex flex-col items-center gap-1.5">
                    <span
                      className={cn(
                        'flex h-8 w-8 items-center justify-center rounded-full border-2 text-[12px] font-semibold',
                        done ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300 bg-white text-neutral-400',
                        activeStep && 'ring-4 ring-primary-100',
                      )}
                    >
                      {done ? <Check className="h-4 w-4" /> : i + 1}
                    </span>
                    <span className={cn('text-[11px] font-medium', done ? 'text-neutral-700' : 'text-neutral-400')}>
                      {t(`loans.status.${step}`)}
                    </span>
                  </div>
                  {i < WORKFLOW.length - 1 && (
                    <span className={cn('mx-2 h-0.5 w-8 sm:w-12', i < currentStep ? 'bg-primary-500' : 'bg-neutral-200')} />
                  )}
                </div>
              )
            })}
          </div>
        </CardBody>
      </Card>

      <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div className="flex flex-col gap-4">
          <StatCard index={0} label={t('loans.outstanding')} value={formatMoney(loan.outstanding)} />
          <Card>
            <CardBody>
              <Progress value={loan.total ? (loan.amountPaid / loan.total) * 100 : 0} showLabel tone={loan.status === 'overdue' ? 'danger' : 'tertiary'} />
              <p className="mt-2 text-[12px] text-neutral-500">
                {formatMoney(loan.amountPaid)} / {formatMoney(loan.total)}
              </p>
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
                  { label: t('loans.fees'), value: formatMoney(loan.fees) },
                  { label: t('loans.insuranceFee'), value: formatMoney(loan.insurance) },
                  { label: t('loans.penalty'), value: formatMoney(loan.penalty) },
                  { label: t('loans.totalRepayable'), value: <span className="font-semibold">{formatMoney(loan.total)}</span> },
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
                { key: 'overview', label: t('loans.tabs.overview') },
                { key: 'schedule', label: t('loans.tabs.schedule'), count: schedule.length },
                { key: 'repayments', label: t('loans.tabs.repayments'), count: repayments.length },
                { key: 'guarantors', label: t('loans.tabs.guarantors'), count: loanGuarantors.length },
              ]}
            />
          </div>

          {tab === 'overview' && (
            <CardBody>
              <DescriptionList
                items={[
                  { label: t('common.member'), value: <MemberCell memberId={loan.memberId} /> },
                  { label: t('loans.product'), value: loan.productName },
                  { label: t('loans.purpose'), value: loan.purpose },
                  { label: t('loans.period'), value: `${loan.period} ${t('loans.months')}` },
                  { label: t('loans.frequency'), value: t(`loans.freq.${loan.frequency}`) },
                  { label: t('loans.interestMethodLabel'), value: t(`loans.method.${loan.interestMethod}`) },
                  { label: t('loans.applicationDate'), value: formatDate(loan.applicationDate) },
                  { label: t('loans.approvalDate'), value: loan.approvalDate ? formatDate(loan.approvalDate) : '—' },
                  { label: t('loans.disbursementDate'), value: loan.disbursementDate ? formatDate(loan.disbursementDate) : '—' },
                  { label: t('loans.maturityDate'), value: loan.maturityDate ? formatDate(loan.maturityDate) : '—' },
                  { label: t('common.status'), value: <StatusBadge status={loan.status} label={t(`loans.status.${loan.status}`)} /> },
                ]}
              />
            </CardBody>
          )}
          {tab === 'schedule' && <DataTable columns={scheduleCols} rows={schedule} rowKey={(r) => r.id} pageSize={24} empty={{ title: t('common.noData'), hint: t('loans.repaymentSchedule') }} />}
          {tab === 'repayments' && <DataTable columns={repayCols} rows={repayments} rowKey={(r) => r.id} empty={{ title: t('common.noData') }} />}
          {tab === 'guarantors' && <DataTable columns={guarantorCols} rows={loanGuarantors} rowKey={(g) => g.id} empty={{ title: t('common.noData') }} />}
        </Card>
      </div>
    </>
  )
}
