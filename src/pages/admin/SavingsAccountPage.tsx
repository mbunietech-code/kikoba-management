import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import {
  Badge, Card, CardBody, DataTable, DescriptionList, PageHeader, StatCard,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { MemberCell } from '@/components/MemberCell'
import { AreaTrend } from '@/components/charts'
import { formatDate, formatMoney } from '@/lib/format'
import { memberById, savingsAccounts, savingsTransactions } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { SavingsTransaction } from '@/types'

export default function SavingsAccountPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { accountId = '' } = useParams()
  const account = savingsAccounts.find((a) => a.id === accountId)
  if (!account) return <NotFoundInline />

  const txns = savingsTransactions
    .filter((s) => s.accountId === accountId)
    .sort((a, b) => +new Date(a.date) - +new Date(b.date))
  const member = memberById(account.memberId)
  const deposits = sum(txns.filter((s) => s.type === 'deposit').map((s) => s.amount))
  const withdrawals = sum(txns.filter((s) => s.type === 'withdrawal').map((s) => s.amount))

  const series = txns.map((s) => ({ month: formatDate(s.date, 'short'), balance: s.balanceAfter }))

  const columns: Column<SavingsTransaction>[] = [
    { key: 'date', header: t('common.date'), sortValue: (s) => s.date, render: (s) => formatDate(s.date) },
    { key: 'type', header: t('common.type'), render: (s) => <Badge tone={s.type === 'deposit' ? 'success' : 'warning'}>{t(`savings.txnType.${s.type}`)}</Badge> },
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.reference}</span> },
    { key: 'before', header: t('savings.balanceBefore'), align: 'right', render: (s) => formatMoney(s.balanceBefore) },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (s) => <span className={s.type === 'deposit' ? 'font-medium text-tertiary-700' : 'font-medium text-danger'}>{s.type === 'deposit' ? '+' : '−'}{formatMoney(s.amount)}</span> },
    { key: 'after', header: t('savings.balanceAfter'), align: 'right', render: (s) => <span className="font-semibold">{formatMoney(s.balanceAfter)}</span> },
  ]

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/admin/savings')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('savings.title')}
          </button>
        }
        title={member?.fullName ?? t('savings.title')}
        subtitle={account.accountNumber}
      />

      <div className="grid gap-4 lg:grid-cols-[300px_1fr]">
        <div className="flex flex-col gap-4">
          <Card>
            <CardBody>
              <DescriptionList
                columns={1}
                items={[
                  { label: t('common.member'), value: <MemberCell memberId={account.memberId} /> },
                  { label: t('savings.accountNumber'), value: account.accountNumber },
                  { label: t('common.status'), value: <Badge tone={account.status === 'active' ? 'success' : 'neutral'}>{account.status}</Badge> },
                  { label: t('common.created'), value: formatDate(account.openedAt) },
                ]}
              />
            </CardBody>
          </Card>
          <StatCard index={0} label={t('common.balance')} value={formatMoney(account.balance)} />
          <div className="grid grid-cols-2 gap-3">
            <StatCard index={1} tone="tertiary" label={t('savings.totalDeposits')} value={formatMoney(deposits, { compact: true })} />
            <StatCard index={2} tone="neutral" label={t('savings.totalWithdrawals')} value={formatMoney(withdrawals, { compact: true })} />
          </div>
        </div>

        <div className="min-w-0">
          <Card>
            <CardBody className="pt-3">
              <AreaTrend data={series.length > 1 ? series : [...series, ...series]} keys={['balance']} colors={['#2563eb']} />
            </CardBody>
          </Card>
          <Card className="mt-4">
            <DataTable columns={columns} rows={[...txns].reverse()} rowKey={(s) => s.id} />
          </Card>
        </div>
      </div>
    </>
  )
}
