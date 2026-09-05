import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Mail, MapPin, Pencil, Phone } from 'lucide-react'
import {
  Avatar, Badge, Button, Card, CardBody, DataTable, DescriptionList, PageHeader,
  StatCard, StatusBadge, Tabs,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { formatDate, formatMoney } from '@/lib/format'
import {
  guarantors, insuranceAccounts, loans, memberById, savingsAccounts, savingsTransactions, shares,
} from '@/mock/data'
import { memberPosition, memberTransactions } from '@/mock/selectors'
import type { Loan, SavingsTransaction, Share, Transaction } from '@/types'

export default function MemberDetailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { id = '' } = useParams()
  const member = memberById(id)
  const [tab, setTab] = useState('profile')

  if (!member) return <NotFoundInline />

  const pos = memberPosition(id)
  const acc = savingsAccounts.find((a) => a.memberId === id)
  const memberShares = shares.filter((s) => s.memberId === id)
  const memberSavingsTxns = savingsTransactions.filter((s) => s.memberId === id)
  const memberLoans = loans.filter((l) => l.memberId === id)
  const memberIns = insuranceAccounts.find((a) => a.memberId === id)
  const guarantees = guarantors.filter((g) => g.guarantorId === id)
  const txns = memberTransactions(id)

  const shareCols: Column<Share>[] = [
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.transactionRef}</span> },
    { key: 'qty', header: t('shares.quantity'), align: 'right', render: (s) => s.quantity },
    { key: 'price', header: t('shares.pricePerShare'), align: 'right', render: (s) => formatMoney(s.pricePerShare) },
    { key: 'value', header: t('shares.totalValue'), align: 'right', render: (s) => <span className="font-medium">{formatMoney(s.totalValue)}</span> },
    { key: 'date', header: t('shares.purchasedAt'), render: (s) => formatDate(s.purchasedAt) },
  ]
  const savingsCols: Column<SavingsTransaction>[] = [
    { key: 'date', header: t('common.date'), sortValue: (s) => s.date, render: (s) => formatDate(s.date) },
    { key: 'type', header: t('common.type'), render: (s) => <Badge tone={s.type === 'deposit' ? 'success' : 'warning'}>{t(`savings.txnType.${s.type}`)}</Badge> },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (s) => <span className={s.type === 'deposit' ? 'text-tertiary-700' : 'text-danger'}>{s.type === 'deposit' ? '+' : '−'}{formatMoney(s.amount)}</span> },
    { key: 'after', header: t('savings.balanceAfter'), align: 'right', render: (s) => formatMoney(s.balanceAfter) },
  ]
  const loanCols: Column<Loan>[] = [
    { key: 'no', header: t('loans.loanNumber'), render: (l) => <Link to={`/admin/loans/${l.id}`} className="font-medium text-primary-700 hover:underline">{l.loanNumber}</Link> },
    { key: 'product', header: t('loans.product'), render: (l) => l.productName },
    { key: 'principal', header: t('loans.principal'), align: 'right', render: (l) => formatMoney(l.principal) },
    { key: 'outstanding', header: t('loans.outstanding'), align: 'right', render: (l) => <span className="font-medium">{formatMoney(l.outstanding)}</span> },
    { key: 'status', header: t('common.status'), render: (l) => <StatusBadge status={l.status} label={t(`loans.status.${l.status}`)} /> },
  ]
  const txnCols: Column<Transaction>[] = [
    { key: 'ref', header: t('transactions.txnRef'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'type', header: t('common.type'), render: (r) => t(`transactions.types.${r.type}`) },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (r) => formatMoney(r.amount) },
    { key: 'date', header: t('common.date'), sortValue: (r) => r.createdAt, render: (r) => formatDate(r.createdAt) },
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
        <div className="flex flex-col gap-4">
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
        </div>

        <div className="min-w-0">
          <div className="grid gap-3 sm:grid-cols-3">
            <StatCard index={0} label={t('member.shareValue')} value={formatMoney(pos.shareValue, { compact: true })} />
            <StatCard index={1} tone="secondary" label={t('member.savingsBalance')} value={formatMoney(pos.savingsBalance, { compact: true })} />
            <StatCard index={2} tone="neutral" label={t('member.outstanding')} value={formatMoney(pos.loanOutstanding, { compact: true })} />
          </div>

          <Card className="mt-4">
            <div className="px-3 pt-2">
              <Tabs
                value={tab}
                onChange={setTab}
                items={[
                  { key: 'profile', label: t('members.tabs.profile') },
                  { key: 'shares', label: t('members.tabs.shares'), count: memberShares.length },
                  { key: 'savings', label: t('members.tabs.savings'), count: memberSavingsTxns.length },
                  { key: 'loans', label: t('members.tabs.loans'), count: memberLoans.length },
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
                    { label: t('members.gender'), value: t(`members.${member.gender}`) },
                    { label: t('members.dob'), value: formatDate(member.dateOfBirth) },
                    { label: t('members.registrationDate'), value: formatDate(member.registrationDate) },
                    { label: t('members.address'), value: member.address },
                    { label: t('members.nextOfKin'), value: `${member.nextOfKin} · ${member.nextOfKinPhone}` },
                    { label: t('savings.accountNumber'), value: acc?.accountNumber ?? '—' },
                    { label: t('common.email'), value: member.email },
                  ]}
                />
              </CardBody>
            )}
            {tab === 'shares' && <DataTable columns={shareCols} rows={memberShares} rowKey={(s) => s.id} empty={{ title: t('common.noData') }} />}
            {tab === 'savings' && <DataTable columns={savingsCols} rows={memberSavingsTxns} rowKey={(s) => s.id} empty={{ title: t('common.noData') }} />}
            {tab === 'loans' && <DataTable columns={loanCols} rows={memberLoans} rowKey={(l) => l.id} onRowClick={(l) => navigate(`/admin/loans/${l.id}`)} empty={{ title: t('common.noData') }} />}
            {tab === 'insurance' && (
              <CardBody>
                {memberIns ? (
                  <DescriptionList
                    items={[
                      { label: t('insurance.planName'), value: memberIns.planName },
                      { label: t('insurance.monthlyContribution'), value: formatMoney(memberIns.monthlyContribution) },
                      { label: t('insurance.coverageAmount'), value: formatMoney(memberIns.coverageAmount) },
                      { label: t('insurance.totalContributions'), value: formatMoney(memberIns.totalContributed) },
                      { label: t('insurance.startDate'), value: formatDate(memberIns.startDate) },
                      { label: t('insurance.endDate'), value: formatDate(memberIns.endDate) },
                      { label: t('common.status'), value: <StatusBadge status={memberIns.status} label={t(`insurance.status.${memberIns.status}`)} /> },
                    ]}
                  />
                ) : (
                  <p className="text-sm text-neutral-400">{t('common.noData')}</p>
                )}
              </CardBody>
            )}
            {tab === 'guarantees' && (
              <CardBody className="!p-0">
                <DataTable
                  columns={[
                    { key: 'loan', header: t('loans.loanNumber'), render: (g) => <Link to={`/admin/loans/${g.loanId}`} className="font-medium text-primary-700 hover:underline">{g.loanNumber}</Link> },
                    { key: 'borrower', header: t('guarantors.borrower'), render: (g) => memberById(g.borrowerId)?.fullName },
                    { key: 'amount', header: t('guarantors.guaranteedAmount'), align: 'right', render: (g) => formatMoney(g.guaranteedAmount) },
                    { key: 'status', header: t('common.status'), render: (g) => <StatusBadge status={g.status} label={t(`guarantors.status.${g.status}`)} /> },
                  ]}
                  rows={guarantees}
                  rowKey={(g) => g.id}
                  empty={{ title: t('common.noData') }}
                />
              </CardBody>
            )}
            {tab === 'transactions' && <DataTable columns={txnCols} rows={txns} rowKey={(r) => r.id} empty={{ title: t('common.noData') }} />}
          </Card>
        </div>
      </div>
    </>
  )
}
