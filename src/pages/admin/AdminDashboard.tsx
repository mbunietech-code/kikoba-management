import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowUpRight, Banknote, HandCoins, PiggyBank, TrendingUp, Users, Wallet } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, PageHeader, SkeletonCard, SkeletonTable, StatCard,
  StatusBadge, EmptyState,
} from '@/components/ui'
import { AreaTrend, DonutChart } from '@/components/charts'
import { Avatar } from '@/components/ui'
import { useApiQuery } from '@/lib/useApi'
import { getDashboard } from '@/api'
import { formatMoney, formatPercent, formatDate, initials } from '@/lib/format'
import { useSession } from '@/app/session'

function MiniMember({ m }: { m: any }) {
  if (!m?.fullName) return <span className="text-neutral-400">—</span>
  return (
    <span className="flex items-center gap-2.5">
      <Avatar name={m.fullName} color={m.avatarColor} size="sm" />
      <span className="min-w-0">
        <span className="block truncate text-[13.5px] font-medium text-neutral-800">{m.fullName}</span>
        <span className="block text-[11px] text-neutral-400">{m.memberNumber}</span>
      </span>
    </span>
  )
}

export default function AdminDashboard() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { session } = useSession()
  const { data, loading, error, refetch } = useApiQuery(() => getDashboard(), [])

  const s = data?.summary
  const loanStatusEntries = Object.entries(data?.loanStatus ?? {}) as [string, number][]

  return (
    <>
      <PageHeader
        title={t('dashboard.welcome', { name: session?.name?.split(' ')[0] ?? '' })}
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

      {error ? (
        <Card><EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} /></Card>
      ) : loading || !s ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => <SkeletonCard key={i} />)}
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard index={0} label={t('dashboard.totalMembers')} value={s.totalMembers} icon={<Users className="h-4 w-4" />} delta={{ value: `+${s.newMembers}`, direction: 'up' }} hint={t('dashboard.newMembers').toLowerCase()} />
            <StatCard index={1} tone="secondary" label={t('dashboard.totalSavings')} value={formatMoney(s.totalSavings, { compact: true })} icon={<PiggyBank className="h-4 w-4" />} />
            <StatCard index={2} tone="tertiary" label={t('dashboard.totalShares')} value={formatMoney(s.totalShares, { compact: true })} icon={<Wallet className="h-4 w-4" />} />
            <StatCard index={3} tone="neutral" label={t('dashboard.outstandingLoans')} value={formatMoney(s.outstanding, { compact: true })} icon={<HandCoins className="h-4 w-4" />} hint={`${t('dashboard.portfolioAtRisk')} ${formatPercent(s.par * 100)}`} />
            <StatCard index={4} label={t('dashboard.totalLoans')} value={formatMoney(s.disbursed, { compact: true })} icon={<Banknote className="h-4 w-4" />} />
            <StatCard index={5} tone="tertiary" label={t('dashboard.totalRepayments')} value={formatMoney(s.repayments, { compact: true })} icon={<TrendingUp className="h-4 w-4" />} />
            <StatCard index={6} tone="secondary" label={t('dashboard.totalProfit')} value={formatMoney(s.netProfit, { compact: true })} icon={<TrendingUp className="h-4 w-4" />} />
            <StatCard index={7} tone="neutral" label={t('dashboard.projectCapital')} value={formatMoney(s.projectCapital, { compact: true })} icon={<Wallet className="h-4 w-4" />} hint={`${s.activeProjects} ${t('dashboard.activeProjects').toLowerCase()}`} />
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader title={t('dashboard.cashFlow')} subtitle="Mar – Sep 2026" />
              <CardBody className="pt-2">
                <AreaTrend data={data.cashFlow} keys={['inflow', 'outflow']} colors={['#115e59', '#dc2626']} />
              </CardBody>
            </Card>
            <Card>
              <CardHeader title={t('dashboard.loanStatus')} />
              <CardBody className="pt-2">
                <DonutChart data={loanStatusEntries.map(([status, count]) => ({ status: t(`loans.status.${status}`), count }))} />
              </CardBody>
            </Card>
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader title={t('dashboard.savingsVsLoans')} />
              <CardBody className="pt-2">
                <AreaTrend data={data.savingsVsLoans} keys={['savings', 'loans']} colors={['#2563eb', '#16a34a']} />
              </CardBody>
            </Card>
            <Card>
              <CardHeader title={t('dashboard.portfolioAtRisk')} />
              <CardBody>
                <p className="font-display text-3xl font-bold text-neutral-900">{formatPercent(s.par * 100)}</p>
                <p className="mt-1 text-[13px] text-neutral-500">{formatMoney(s.outstanding, { compact: true })} {t('dashboard.outstandingLoans').toLowerCase()}</p>
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
                {data.pendingApprovals.length === 0 && <p className="p-5 text-sm text-neutral-400">{t('common.noData')}</p>}
                {data.pendingApprovals.map((l: any) => (
                  <Link key={l.id} to={`/admin/loans/${l.id}`} className="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
                    <div className="min-w-0 flex-1"><MiniMember m={l.member} /></div>
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
                {data.upcomingRepayments.map((r: any) => (
                  <Link key={r.id} to={`/admin/loans/${r.loanId}`} className="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
                    <div className="min-w-0 flex-1"><MiniMember m={r.member} /></div>
                    <div className="text-right">
                      <p className="text-[13px] font-semibold text-neutral-800">{formatMoney(r.totalDue, { compact: true })}</p>
                      <p className="text-[11px] text-neutral-400">{formatDate(r.dueDate)}</p>
                    </div>
                    <Badge tone={r.status === 'overdue' ? 'danger' : 'warning'} dot>
                      {t(`loans.scheduleStatus.${r.status}`)}
                    </Badge>
                  </Link>
                ))}
              </CardBody>
            </Card>
          </div>

          <Card className="mt-4">
            <CardHeader
              title={t('dashboard.recentTransactions')}
              action={<Link to="/admin/payments" className="inline-flex items-center gap-1 text-[13px] font-medium text-primary-700 hover:underline">{t('common.viewAll')} <ArrowUpRight className="h-3.5 w-3.5" /></Link>}
            />
            {loading ? <SkeletonTable /> : (
              <CardBody className="!p-0">
                <ul className="divide-y divide-neutral-100">
                  {data.recentTransactions.map((tx: any) => (
                    <li key={tx.id} className="flex items-center gap-3 px-5 py-3">
                      <span className="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-[11px] font-semibold text-neutral-500">
                        {tx.member?.fullName ? initials(tx.member.fullName) : '—'}
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="text-[13px] font-medium text-neutral-800">{t(`transactions.types.${tx.type}`)}</p>
                        <p className="text-[11px] text-neutral-400">{tx.reference}</p>
                      </div>
                      <span className="text-[13px] font-semibold text-neutral-800">{formatMoney(tx.amount)}</span>
                      <StatusBadge status={tx.status} />
                    </li>
                  ))}
                </ul>
              </CardBody>
            )}
          </Card>
        </>
      )}
    </>
  )
}
