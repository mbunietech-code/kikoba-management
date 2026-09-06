import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Mail, MapPin, Pencil, Phone } from 'lucide-react'
import {
  Avatar, Badge, Button, Card, CardBody, DataTable, DescriptionList, PageHeader, StatCard,
  StatusBadge, Tabs, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { useApiQuery } from '@/lib/useApi'
import { getMember } from '@/api'
import { formatDate, formatMoney } from '@/lib/format'

export default function MemberDetailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { id = '' } = useParams()
  const [tab, setTab] = useState('profile')
  const { data: member, loading, error } = useApiQuery(() => getMember(id), [id])

  if (loading) return <div className="card p-10"><EmptyState title={t('common.loading')} /></div>
  if (error || !member) return <Card className="mt-6"><EmptyState title={t('errors.notFound')} hint={error?.message} action={<Button onClick={() => navigate('/admin/members')}>{t('common.back')}</Button>} /></Card>

  const shares = member.shares ?? []
  const savingsTxns = member.savingsTransactions ?? []
  const loans = member.loans ?? []
  const ins = member.insuranceAccount
  const guarantees = member.guaranteeing ?? []
  const txns = member.transactions ?? []
  const shareValue = shares.reduce((a: number, s: any) => a + (s.totalValue ?? 0), 0)

  const shareCols: Column<any>[] = [
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.transactionReference}</span> },
    { key: 'qty', header: t('shares.quantity'), align: 'right', render: (s) => s.quantity },
    { key: 'price', header: t('shares.pricePerShare'), align: 'right', render: (s) => formatMoney(s.pricePerShare) },
    { key: 'value', header: t('shares.totalValue'), align: 'right', render: (s) => <span className="font-medium">{formatMoney(s.totalValue)}</span> },
    { key: 'date', header: t('shares.purchasedAt'), render: (s) => formatDate(s.purchasedAt) },
  ]
  const savingsCols: Column<any>[] = [
    { key: 'date', header: t('common.date'), render: (s) => formatDate(s.createdAt) },
    { key: 'type', header: t('common.type'), render: (s) => <Badge tone={s.type === 'deposit' ? 'success' : 'warning'}>{t(`savings.txnType.${s.type}`)}</Badge> },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (s) => <span className={s.type === 'deposit' ? 'text-tertiary-700' : 'text-danger'}>{s.type === 'deposit' ? '+' : '−'}{formatMoney(s.amount)}</span> },
    { key: 'after', header: t('savings.balanceAfter'), align: 'right', render: (s) => formatMoney(s.balanceAfter) },
  ]
  const loanCols: Column<any>[] = [
    { key: 'no', header: t('loans.loanNumber'), render: (l) => <Link to={`/admin/loans/${l.id}`} className="font-medium text-primary-700 hover:underline">{l.loanNumber}</Link> },
    { key: 'product', header: t('loans.product'), render: (l) => l.product?.name },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (l) => formatMoney(l.principalAmount) },
    { key: 'outstanding', header: t('loans.outstanding'), align: 'right', render: (l) => <span className="font-medium">{formatMoney(l.outstandingBalance)}</span> },
    { key: 'status', header: t('common.status'), render: (l) => <StatusBadge status={l.status} label={t(`loans.status.${l.status}`)} /> },
  ]
  const txnCols: Column<any>[] = [
    { key: 'ref', header: t('transactions.txnRef'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.transactionReference}</span> },
    { key: 'type', header: t('common.type'), render: (r) => t(`transactions.types.${String(r.type).toLowerCase()}`) },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (r) => formatMoney(r.amount) },
    { key: 'date', header: t('common.date'), render: (r) => formatDate(r.createdAt) },
    { key: 'status', header: t('common.status'), render: (r) => <StatusBadge status={r.status} /> },
  ]

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/admin/members')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('members.title')}
          </button>
        }
        title={member.fullName}
        subtitle={member.memberNumber}
        actions={<Button variant="outlined" leftIcon={<Pencil className="h-4 w-4" />} onClick={() => navigate(`/admin/members/${id}/edit`)}>{t('common.edit')}</Button>}
      />

      <div className="grid gap-4 lg:grid-cols-[300px_1fr]">
        <Card>
          <CardBody className="flex flex-col items-center text-center">
            <Avatar name={member.fullName} color={member.avatarColor} size="lg" />
            <p className="mt-3 font-display text-lg font-bold text-neutral-900">{member.fullName}</p>
            <StatusBadge status={member.status} label={t(`members.status.${member.status}`)} />
            <div className="mt-4 w-full space-y-2 text-left text-[13px] text-neutral-600">
              <p className="flex items-center gap-2"><Phone className="h-4 w-4 text-neutral-400" /> {member.phone}</p>
              <p className="flex items-center gap-2"><Mail className="h-4 w-4 text-neutral-400" /> {member.email}</p>
              <p className="flex items-center gap-2"><MapPin className="h-4 w-4 text-neutral-400" /> {member.address}</p>
            </div>
          </CardBody>
        </Card>

        <div className="min-w-0">
          <div className="grid gap-3 sm:grid-cols-3">
            <StatCard index={0} label={t('member.shareValue')} value={formatMoney(shareValue, { compact: true })} />
            <StatCard index={1} tone="secondary" label={t('member.savingsBalance')} value={formatMoney(member.savingsAccount?.balance ?? 0, { compact: true })} />
            <StatCard index={2} tone="neutral" label={t('member.outstanding')} value={formatMoney(loans.reduce((a: number, l: any) => a + (l.outstandingBalance ?? 0), 0), { compact: true })} />
          </div>

          <Card className="mt-4">
            <div className="px-3 pt-2">
              <Tabs
                value={tab}
                onChange={setTab}
                items={[
                  { key: 'profile', label: t('members.tabs.profile') },
                  { key: 'shares', label: t('members.tabs.shares'), count: shares.length },
                  { key: 'savings', label: t('members.tabs.savings'), count: savingsTxns.length },
                  { key: 'loans', label: t('members.tabs.loans'), count: loans.length },
                  { key: 'insurance', label: t('members.tabs.insurance') },
                  { key: 'guarantees', label: t('members.tabs.guarantees'), count: guarantees.length },
                  { key: 'transactions', label: t('members.tabs.transactions'), count: txns.length },
                ]}
              />
            </div>

            {tab === 'profile' && (
              <CardBody>
                <DescriptionList
                  items={[
                    { label: t('members.memberNumber'), value: member.memberNumber },
                    { label: t('members.gender'), value: member.gender ? t(`members.${member.gender}`) : '—' },
                    { label: t('members.dob'), value: member.dateOfBirth ? formatDate(member.dateOfBirth) : '—' },
                    { label: t('members.registrationDate'), value: formatDate(member.registrationDate) },
                    { label: t('members.address'), value: member.address },
                    { label: t('members.nextOfKin'), value: `${member.nextOfKin ?? '—'} · ${member.nextOfKinPhone ?? ''}` },
                    { label: t('savings.accountNumber'), value: member.savingsAccount?.accountNumber ?? '—' },
                    { label: t('common.email'), value: member.email },
                  ]}
                />
              </CardBody>
            )}
            {tab === 'shares' && <DataTable columns={shareCols} rows={shares} rowKey={(s: any) => s.id} empty={{ title: t('common.noData') }} />}
            {tab === 'savings' && <DataTable columns={savingsCols} rows={savingsTxns} rowKey={(s: any) => s.id} empty={{ title: t('common.noData') }} />}
            {tab === 'loans' && <DataTable columns={loanCols} rows={loans} rowKey={(l: any) => l.id} onRowClick={(l: any) => navigate(`/admin/loans/${l.id}`)} empty={{ title: t('common.noData') }} />}
            {tab === 'insurance' && (
              <CardBody>
                {ins ? (
                  <DescriptionList
                    items={[
                      { label: t('insurance.planName'), value: ins.planName },
                      { label: t('insurance.monthlyContribution'), value: formatMoney(ins.monthlyContribution) },
                      { label: t('insurance.coverageAmount'), value: formatMoney(ins.coverageAmount) },
                      { label: t('insurance.startDate'), value: formatDate(ins.startDate) },
                      { label: t('insurance.endDate'), value: ins.endDate ? formatDate(ins.endDate) : '—' },
                      { label: t('common.status'), value: <StatusBadge status={ins.status} label={t(`insurance.status.${ins.status}`)} /> },
                    ]}
                  />
                ) : (
                  <p className="text-sm text-neutral-400">{t('common.noData')}</p>
                )}
              </CardBody>
            )}
            {tab === 'guarantees' && (
              <DataTable
                columns={[
                  { key: 'loan', header: t('loans.loanNumber'), render: (g: any) => <Link to={`/admin/loans/${g.loanId}`} className="font-medium text-primary-700 hover:underline">{g.loan?.loanNumber}</Link> },
                  { key: 'amount', header: t('guarantors.guaranteedAmount'), align: 'right', render: (g: any) => formatMoney(g.guaranteedAmount) },
                  { key: 'status', header: t('common.status'), render: (g: any) => <StatusBadge status={g.status} label={t(`guarantors.status.${g.status}`)} /> },
                ]}
                rows={guarantees}
                rowKey={(g: any) => g.id}
                empty={{ title: t('common.noData') }}
              />
            )}
            {tab === 'transactions' && <DataTable columns={txnCols} rows={txns} rowKey={(r: any) => r.id} empty={{ title: t('common.noData') }} />}
          </Card>
        </div>
      </div>
    </>
  )
}
