import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Check, X } from 'lucide-react'
import {
  Avatar, Badge, Button, Card, CardBody, CardHeader, DataTable, DescriptionList, PageHeader, Progress,
  StatCard, StatusBadge, Tabs, useToast, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { useApiQuery } from '@/lib/useApi'
import { approveLoan, disburseLoan, getLoan, rejectLoan, repayLoan } from '@/api'
import { formatDate, formatMoney } from '@/lib/format'
import { cn } from '@/lib/cn'

const WORKFLOW = ['submitted', 'under_review', 'approved', 'disbursed', 'active', 'completed'] as const

export default function LoanDetailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { id = '' } = useParams()
  const [tab, setTab] = useState('overview')
  const { data: loan, loading, error, refetch } = useApiQuery(() => getLoan(id), [id])

  if (loading) return <div className="card p-10"><EmptyState title={t('common.loading')} /></div>
  if (error || !loan) return <Card className="mt-6"><EmptyState title={t('errors.notFound')} hint={error?.message} action={<Button onClick={() => navigate('/admin/loans')}>{t('common.back')}</Button>} /></Card>

  const schedule = loan.schedule ?? []
  const repayments = loan.repayments ?? []
  const guarantors = loan.guarantors ?? []
  const currentStep = WORKFLOW.indexOf(loan.status)
  const canDecide = ['submitted', 'under_review'].includes(loan.status)

  async function act(fn: () => Promise<unknown>, msg: string, tone: 'success' | 'error' = 'success') {
    try {
      await fn()
      toast(msg, tone)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  const scheduleCols: Column<any>[] = [
    { key: 'n', header: '#', render: (r) => r.installmentNumber },
    { key: 'due', header: t('loans.dueDate'), render: (r) => formatDate(r.dueDate) },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (r) => formatMoney(r.principalDue) },
    { key: 'interest', header: t('loans.interest'), align: 'right', render: (r) => formatMoney(r.interestDue) },
    { key: 'total', header: t('loans.amountDue'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalDue)}</span> },
    { key: 'paid', header: t('loans.amountPaid'), align: 'right', render: (r) => formatMoney(r.amountPaid) },
    { key: 'status', header: t('common.status'), render: (r) => <StatusBadge status={r.status} label={t(`loans.scheduleStatus.${r.status}`)} /> },
  ]
  const repayCols: Column<any>[] = [
    { key: 'date', header: t('common.date'), render: (r) => formatDate(r.paymentDate) },
    { key: 'ref', header: t('common.reference'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (r) => formatMoney(r.principalPaid) },
    { key: 'interest', header: t('loans.interest'), align: 'right', render: (r) => formatMoney(r.interestPaid) },
    { key: 'total', header: t('common.total'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.totalPaid)}</span> },
    { key: 'method', header: t('payments.method'), render: (r) => <Badge tone="info">{t(`payments.methods.${r.method}`)}</Badge> },
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
        subtitle={`${loan.product?.name ?? ''} · ${loan.member?.fullName ?? ''}`}
        actions={
          canDecide ? (
            <>
              <Button variant="danger" leftIcon={<X className="h-4 w-4" />} onClick={() => act(() => rejectLoan(id), `${t('common.reject')} ✓`, 'error')}>{t('common.reject')}</Button>
              <Button leftIcon={<Check className="h-4 w-4" />} onClick={() => act(() => approveLoan(id), `${t('common.approve')} ✓`)}>{t('common.approve')}</Button>
            </>
          ) : loan.status === 'approved' ? (
            <Button onClick={() => act(() => disburseLoan(id), `${t('loans.disburse')} ✓`)}>{t('loans.disburse')}</Button>
          ) : ['active', 'overdue', 'disbursed'].includes(loan.status) ? (
            <Button onClick={() => act(() => repayLoan(id, { amount: Math.round(loan.outstandingBalance / Math.max(1, loan.period)), method: 'mobile_money' }), `${t('loans.recordRepayment')} ✓`)}>{t('loans.recordRepayment')}</Button>
          ) : null
        }
      />

      <Card className="mb-4">
        <CardBody>
          <div className="flex flex-wrap items-center gap-y-3">
            {WORKFLOW.map((step, i) => {
              const done = currentStep >= 0 && i <= currentStep
              return (
                <div key={step} className="flex items-center">
                  <div className="flex flex-col items-center gap-1.5">
                    <span className={cn('flex h-8 w-8 items-center justify-center rounded-full border-2 text-[12px] font-semibold', done ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300 bg-white text-neutral-400', i === currentStep && 'ring-4 ring-primary-100')}>
                      {done ? <Check className="h-4 w-4" /> : i + 1}
                    </span>
                    <span className={cn('text-[11px] font-medium', done ? 'text-neutral-700' : 'text-neutral-400')}>{t(`loans.status.${step}`)}</span>
                  </div>
                  {i < WORKFLOW.length - 1 && <span className={cn('mx-2 h-0.5 w-8 sm:w-12', i < currentStep ? 'bg-primary-500' : 'bg-neutral-200')} />}
                </div>
              )
            })}
          </div>
        </CardBody>
      </Card>

      <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div className="flex flex-col gap-4">
          <StatCard index={0} label={t('loans.outstanding')} value={formatMoney(loan.outstandingBalance)} />
          <Card>
            <CardBody>
              <Progress value={loan.totalAmount ? (loan.amountPaid / loan.totalAmount) * 100 : 0} showLabel tone={loan.status === 'overdue' ? 'danger' : 'tertiary'} />
              <p className="mt-2 text-[12px] text-neutral-500">{formatMoney(loan.amountPaid)} / {formatMoney(loan.totalAmount)}</p>
            </CardBody>
          </Card>
          <Card>
            <CardHeader title={t('common.summary')} className="!py-3" />
            <CardBody>
              <DescriptionList
                columns={1}
                items={[
                  { label: t('loans.principal'), value: formatMoney(loan.principalAmount) },
                  { label: t('loans.interest'), value: formatMoney(loan.interestAmount) },
                  { label: t('loans.fees'), value: formatMoney(loan.processingFee) },
                  { label: t('loans.insuranceFee'), value: formatMoney(loan.insuranceAmount) },
                  { label: t('loans.penalty'), value: formatMoney(loan.penaltyAmount) },
                  { label: t('loans.totalRepayable'), value: <span className="font-semibold">{formatMoney(loan.totalAmount)}</span> },
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
                { key: 'guarantors', label: t('loans.tabs.guarantors'), count: guarantors.length },
              ]}
            />
          </div>

          {tab === 'overview' && (
            <CardBody>
              <DescriptionList
                items={[
                  { label: t('common.member'), value: loan.member ? <span className="flex items-center gap-2"><Avatar name={loan.member.fullName} color={loan.member.avatarColor} size="xs" />{loan.member.fullName}</span> : '—' },
                  { label: t('loans.product'), value: loan.product?.name },
                  { label: t('loans.purpose'), value: loan.purpose },
                  { label: t('loans.period'), value: `${loan.period} ${t('loans.months')}` },
                  { label: t('loans.frequency'), value: t(`loans.freq.${loan.repaymentFrequency}`) },
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
          {tab === 'schedule' && <DataTable columns={scheduleCols} rows={schedule} rowKey={(r: any) => r.id} pageSize={24} empty={{ title: t('common.noData') }} />}
          {tab === 'repayments' && <DataTable columns={repayCols} rows={repayments} rowKey={(r: any) => r.id} empty={{ title: t('common.noData') }} />}
          {tab === 'guarantors' && (
            <DataTable
              columns={[
                { key: 'g', header: t('guarantors.guarantor'), render: (g: any) => g.guarantorMember?.fullName ?? '—' },
                { key: 'amt', header: t('guarantors.guaranteedAmount'), align: 'right', render: (g: any) => formatMoney(g.guaranteedAmount) },
                { key: 'status', header: t('common.status'), render: (g: any) => <StatusBadge status={g.status} label={t(`guarantors.status.${g.status}`)} /> },
              ]}
              rows={guarantors}
              rowKey={(g: any) => g.id}
              empty={{ title: t('common.noData') }}
            />
          )}
        </Card>
      </div>
    </>
  )
}
