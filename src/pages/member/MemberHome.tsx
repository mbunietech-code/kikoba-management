import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowRight, Coins, Download, HandCoins, PiggyBank, PlusCircle } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, PageHeader, SkeletonCard, StatCard, StatusBadge,
} from '@/components/ui'
import { AreaTrend } from '@/components/charts'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDate, formatMoney } from '@/lib/format'
import { memberPosition, memberTransactions } from '@/mock/selectors'
import { repaymentSchedules } from '@/mock/data'
import { useCurrentMember, useMemberId } from './useMember'
import { cn } from '@/lib/cn'

export default function MemberHome() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const memberId = useMemberId()
  const member = useCurrentMember()
  const { data, loading } = useMockQuery(() => memberPosition(memberId), [memberId])

  const txns = memberTransactions(memberId).slice(0, 6)
  const nextRepay = data?.activeLoan
    ? repaymentSchedules
        .filter((r) => r.loanId === data.activeLoan!.id && (r.status === 'pending' || r.status === 'overdue'))
        .sort((a, b) => +new Date(a.dueDate) - +new Date(b.dueDate))[0]
    : undefined

  const savingsSeries = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'].map((m, i) => ({
    month: m,
    savings: (data?.savingsBalance ?? 0) * (0.6 + i * 0.08),
  }))

  const actions = [
    { label: t('member.applyLoan'), icon: HandCoins, to: '/member/loans/apply' },
    { label: t('member.makeDeposit'), icon: PiggyBank, to: '/member/savings' },
    { label: t('member.buyShares'), icon: PlusCircle, to: '/member/shares' },
    { label: t('member.downloadStatement'), icon: Download, to: '/member/statements' },
  ]

  return (
    <>
      <PageHeader title={t('member.greeting', { name: member.fullName.split(' ')[0] })} subtitle={t('member.myPosition')} />

      {loading || !data ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => <SkeletonCard key={i} />)}
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard index={0} label={t('member.shareValue')} value={formatMoney(data.shareValue, { compact: true })} hint={`${data.shareQty} ${t('shares.quantity').toLowerCase()}`} />
            <StatCard index={1} tone="secondary" label={t('member.savingsBalance')} value={formatMoney(data.savingsBalance, { compact: true })} />
            <StatCard index={2} tone="neutral" label={t('member.outstanding')} value={formatMoney(data.loanOutstanding, { compact: true })} />
            <StatCard index={3} tone="tertiary" label={t('member.myProfit')} value={formatMoney(data.profit, { compact: true })} />
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader title={t('member.savingsBalance')} subtitle="Apr – Sep 2026" />
              <CardBody className="pt-2">
                <AreaTrend data={savingsSeries} keys={['savings']} colors={['#2563eb']} />
              </CardBody>
            </Card>

            <div className="flex flex-col gap-4">
              {nextRepay && (
                <Card>
                  <CardBody>
                    <p className="text-[13px] font-medium text-neutral-500">{t('member.nextRepayment')}</p>
                    <p className="mt-1 font-display text-xl font-bold text-neutral-900">{formatMoney(nextRepay.totalDue)}</p>
                    <div className="mt-1 flex items-center gap-2">
                      <span className="text-[13px] text-neutral-500">{formatDate(nextRepay.dueDate)}</span>
                      {nextRepay.status === 'overdue' && <Badge tone="danger" dot>{t('loans.scheduleStatus.overdue')}</Badge>}
                    </div>
                    <Button size="sm" className="mt-3 w-full" onClick={() => navigate(`/member/loans/${data.activeLoan!.id}`)}>
                      {t('loans.recordRepayment')}
                    </Button>
                  </CardBody>
                </Card>
              )}
              <Card>
                <CardBody>
                  <p className="text-[13px] font-medium text-neutral-500">{t('member.insuranceStatus')}</p>
                  <div className="mt-1.5">
                    {data.insurance ? (
                      <StatusBadge status={data.insurance.status} label={t(`insurance.status.${data.insurance.status}`)} />
                    ) : (
                      <Badge tone="neutral">—</Badge>
                    )}
                  </div>
                  {data.insurance && (
                    <p className="mt-2 text-[13px] text-neutral-500">
                      {t('insurance.coverageAmount')}: {formatMoney(data.insurance.coverageAmount)}
                    </p>
                  )}
                </CardBody>
              </Card>
            </div>
          </div>

          <Card className="mt-4">
            <CardHeader title={t('member.quickActions')} />
            <CardBody className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              {actions.map((a, i) => (
                <button
                  key={i}
                  onClick={() => navigate(a.to)}
                  className={cn(
                    'flex items-center gap-3 rounded-xl border border-neutral-200 p-4 text-left transition-all hover:border-primary-300 hover:bg-primary-50/50',
                  )}
                >
                  <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                    <a.icon className="h-5 w-5" />
                  </span>
                  <span className="text-[13.5px] font-medium text-neutral-700">{a.label}</span>
                </button>
              ))}
            </CardBody>
          </Card>

          <Card className="mt-4">
            <CardHeader
              title={t('common.recent') + ' — ' + t('nav.transactions')}
              action={<Link to="/member/transactions" className="inline-flex items-center gap-1 text-[13px] font-medium text-primary-700 hover:underline">{t('common.viewAll')} <ArrowRight className="h-3.5 w-3.5" /></Link>}
            />
            <CardBody className="!p-0">
              <ul className="divide-y divide-neutral-100">
                {txns.map((tx) => (
                  <li key={tx.id} className="flex items-center gap-3 px-5 py-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-100 text-neutral-500">
                      <Coins className="h-4 w-4" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="text-[13.5px] font-medium text-neutral-800">{t(`transactions.types.${tx.type}`)}</p>
                      <p className="text-[12px] text-neutral-400">{tx.reference} · {formatDate(tx.createdAt)}</p>
                    </div>
                    <span className="text-[13.5px] font-semibold text-neutral-800">{formatMoney(tx.amount)}</span>
                  </li>
                ))}
              </ul>
            </CardBody>
          </Card>
        </>
      )}
    </>
  )
}
