import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  ArrowUpRight, Banknote, HandCoins, PiggyBank, TrendingUp, Users, Wallet,
} from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, DataTable, PageHeader, SkeletonCard, SkeletonTable,
  StatCard, StatusBadge,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { AreaTrend, DonutChart, MiniBars } from '@/components/charts'
import { MemberCell } from '@/components/MemberCell'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatMoney, formatPercent, formatDate } from '@/lib/format'
import {
  cashFlowSeries, contributionTrend, groupSummary, loanStatusBreakdown, savingsVsLoansSeries,
} from '@/mock/selectors'
import { loans, repaymentSchedules, transactions } from '@/mock/data'
import { useSession } from '@/app/session'
import type { Transaction } from '@/types'

export default function AdminDashboard() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { session } = useSession()
  const { data, loading } = useMockQuery(() => ({
    s: groupSummary(),
    cash: cashFlowSeries(),
    svl: savingsVsLoansSeries(),
    status: loanStatusBreakdown(),
    contrib: contributionTrend(),
  }))

  const pendingApprovals = loans.filter((l) => ['submitted', 'under_review'].includes(l.status))
  const upcoming = repaymentSchedules
    .filter((r) => r.status === 'pending' || r.status === 'overdue')
    .sort((a, b) => +new Date(a.dueDate) - +new Date(b.dueDate))
    .slice(0, 5)
  const recentTxns = transactions.slice(0, 6)

  const txnCols: Column<Transaction>[] = [
    { key: 'ref', header: t('transactions.txnRef'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'member', header: t('common.member'), render: (r) => <MemberCell memberId={r.memberId} /> },
    { key: 'type', header: t('common.type'), render: (r) => <span className="text-[13px]">{t(`transactions.types.${r.type}`)}</span> },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (r) => <span className="font-medium">{formatMoney(r.amount)}</span> },
    { key: 'status', header: t('common.status'), render: (r) => <StatusBadge status={r.status} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('dashboard.welcome', { name: session?.name?.split(' ')[0] })}
        subtitle={t('dashboard.overview')}
        actions={
          <>
            <Button variant="outlined" onClick={() => navigate('/admin/reports')}>{t('nav.reports')}</Button>
            <Button leftIcon={<Users className="h-4 w-4" />} onClick={() => navigate('/admin/members/new')}>
              {t('members.addMember')}
            </Button>
          </>
        }
      />

      {loading || !data ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => <SkeletonCard key={i} />)}
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard index={0} label={t('dashboard.totalMembers')} value={data.s.totalMembers} icon={<Users className="h-4 w-4" />} delta={{ value: `+${data.s.newMembers}`, direction: 'up' }} hint={t('dashboard.newMembers').toLowerCase()} />
            <StatCard index={1} tone="secondary" label={t('dashboard.totalSavings')} value={formatMoney(data.s.totalSavings, { compact: true })} icon={<PiggyBank className="h-4 w-4" />} delta={{ value: '6.4%', direction: 'up' }} />
            <StatCard index={2} tone="tertiary" label={t('dashboard.totalShares')} value={formatMoney(data.s.totalShares, { compact: true })} icon={<Wallet className="h-4 w-4" />} delta={{ value: '2.1%', direction: 'up' }} />
            <StatCard index={3} tone="neutral" label={t('dashboard.outstandingLoans')} value={formatMoney(data.s.outstanding, { compact: true })} icon={<HandCoins className="h-4 w-4" />} hint={`${t('dashboard.portfolioAtRisk')} ${formatPercent(data.s.par * 100)}`} />
            <StatCard index={4} label={t('dashboard.totalLoans')} value={formatMoney(data.s.disbursed, { compact: true })} icon={<Banknote className="h-4 w-4" />} />
            <StatCard index={5} tone="tertiary" label={t('dashboard.totalRepayments')} value={formatMoney(data.s.repayments, { compact: true })} icon={<TrendingUp className="h-4 w-4" />} delta={{ value: '9.2%', direction: 'up' }} />
            <StatCard index={6} tone="secondary" label={t('dashboard.totalProfit')} value={formatMoney(data.s.netProfit, { compact: true })} icon={<TrendingUp className="h-4 w-4" />} delta={{ value: '12%', direction: 'up' }} />
            <StatCard index={7} tone="neutral" label={t('dashboard.projectCapital')} value={formatMoney(data.s.projectCapital, { compact: true })} icon={<Wallet className="h-4 w-4" />} hint={`${data.s.activeProjects} ${t('dashboard.activeProjects').toLowerCase()}`} />
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader title={t('dashboard.cashFlow')} subtitle="Mar – Sep 2026" />
              <CardBody className="pt-2">
                <AreaTrend data={data.cash} keys={['inflow', 'outflow']} colors={['#115e59', '#dc2626']} />
              </CardBody>
            </Card>
            <Card>
              <CardHeader title={t('dashboard.loanStatus')} />
              <CardBody className="pt-2">
                <DonutChart data={data.status.map((s) => ({ ...s, status: t(`loans.status.${s.status}`) }))} />
              </CardBody>
            </Card>
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader title={t('dashboard.savingsVsLoans')} />
              <CardBody className="pt-2">
                <AreaTrend data={data.svl} keys={['savings', 'loans']} colors={['#2563eb', '#16a34a']} />
              </CardBody>
            </Card>
            <Card>
              <CardHeader title={t('dashboard.contributionTrend')} subtitle={t('insurance.title')} />
              <CardBody className="pt-2">
                <MiniBars data={data.contrib} color="#0d9488" />
              </CardBody>
            </Card>
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-2">
            <Card>
              <CardHeader
                title={t('dashboard.pendingApprovals')}
                action={<Link to="/admin/loans" className="text-[13px] font-medium text-primary-700 hover:underline">{t('common.viewAll')}</Link>}
              />
              <CardBody className="divide-y divide-neutral-100 !p-0">
                {pendingApprovals.length === 0 && <p className="p-5 text-sm text-neutral-400">{t('common.noData')}</p>}
                {pendingApprovals.map((l) => (
                  <Link key={l.id} to={`/admin/loans/${l.id}`} className="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
                    <div className="min-w-0 flex-1">
                      <MemberCell memberId={l.memberId} link={false} />
                    </div>
                    <div className="text-right">
                      <p className="text-[13px] font-semibold text-neutral-800">{formatMoney(l.principal, { compact: true })}</p>
                      <p className="text-[11px] text-neutral-400">{l.productName}</p>
                    </div>
                    <StatusBadge status={l.status} label={t(`loans.status.${l.status}`)} />
                  </Link>
                ))}
              </CardBody>
            </Card>

            <Card>
              <CardHeader
                title={t('dashboard.upcomingRepayments')}
                action={<Link to="/admin/loans" className="text-[13px] font-medium text-primary-700 hover:underline">{t('common.viewAll')}</Link>}
              />
              <CardBody className="divide-y divide-neutral-100 !p-0">
                {upcoming.map((r) => {
                  const loan = loans.find((l) => l.id === r.loanId)!
                  return (
                    <Link key={r.id} to={`/admin/loans/${r.loanId}`} className="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
                      <div className="min-w-0 flex-1">
                        <MemberCell memberId={loan.memberId} link={false} />
                      </div>
                      <div className="text-right">
                        <p className="text-[13px] font-semibold text-neutral-800">{formatMoney(r.totalDue, { compact: true })}</p>
                        <p className="text-[11px] text-neutral-400">{formatDate(r.dueDate)}</p>
                      </div>
                      {r.status === 'overdue' ? <Badge tone="danger" dot>{t('loans.scheduleStatus.overdue')}</Badge> : <Badge tone="warning" dot>{t('loans.scheduleStatus.pending')}</Badge>}
                    </Link>
                  )
                })}
              </CardBody>
            </Card>
          </div>

          <Card className="mt-4">
            <CardHeader
              title={t('dashboard.recentTransactions')}
              action={<Link to="/admin/payments" className="inline-flex items-center gap-1 text-[13px] font-medium text-primary-700 hover:underline">{t('common.viewAll')} <ArrowUpRight className="h-3.5 w-3.5" /></Link>}
            />
            {loading ? <SkeletonTable /> : <DataTable columns={txnCols} rows={recentTxns} rowKey={(r) => r.id} pageSize={6} />}
          </Card>
        </>
      )}
    </>
  )
}
