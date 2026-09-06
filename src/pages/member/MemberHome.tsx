import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Download, HandCoins, PiggyBank, PlusCircle } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, PageHeader, SkeletonCard, StatCard, StatusBadge, EmptyState,
} from '@/components/ui'
import { AreaTrend } from '@/components/charts'
import { useApiQuery } from '@/lib/useApi'
import { mePosition, meTransactions } from '@/api'
import { formatMoney } from '@/lib/format'
import { cn } from '@/lib/cn'

export default function MemberHome() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { data, loading, error, refetch } = useApiQuery(() => mePosition(), [])
  const { data: txns } = useApiQuery(() => meTransactions(), [])

  const firstName = data?.member?.fullName?.split(' ')[0] ?? ''

  const savingsSeries = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'].map((m, i) => ({
    month: m,
    savings: Math.round((data?.savingsBalance ?? 0) * (0.6 + i * 0.08)),
  }))

  const actions = [
    { label: t('member.applyLoan'), icon: HandCoins, to: '/member/loans/apply' },
    { label: t('member.makeDeposit'), icon: PiggyBank, to: '/member/savings' },
    { label: t('member.buyShares'), icon: PlusCircle, to: '/member/shares' },
    { label: t('member.downloadStatement'), icon: Download, to: '/member/statements' },
  ]

  return (
    <>
      <PageHeader title={t('member.greeting', { name: firstName })} subtitle={t('member.myPosition')} />

      {error ? (
        <Card><EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} /></Card>
      ) : loading || !data ? (
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

          <Card className="mt-4">
            <CardHeader title={t('member.quickActions')} />
            <CardBody className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              {actions.map((a, i) => (
                <button
                  key={i}
                  onClick={() => navigate(a.to)}
                  className={cn('flex items-center gap-3 rounded-xl border border-neutral-200 p-4 text-left transition-all hover:border-primary-300 hover:bg-primary-50/50')}
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
            <CardHeader title={`${t('common.recent')} — ${t('nav.transactions')}`} />
            <CardBody className="!p-0">
              <ul className="divide-y divide-neutral-100">
                {(txns ?? []).slice(0, 6).map((tx: any) => (
                  <li key={tx.id} className="flex items-center justify-between px-5 py-3">
                    <div>
                      <p className="text-[13.5px] font-medium text-neutral-800">{t(`transactions.types.${tx.type}`)}</p>
                      <p className="text-[12px] text-neutral-400">{tx.reference}</p>
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
