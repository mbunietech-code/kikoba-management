import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { ArrowDownToLine } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, DataTable, Field, Input, Modal, PageHeader, StatCard, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { AreaTrend } from '@/components/charts'
import { formatDate, formatMoney } from '@/lib/format'
import { savingsAccounts, savingsTransactions } from '@/mock/data'
import { sum } from '@/mock/selectors'
import { useMemberId } from './useMember'
import type { SavingsTransaction } from '@/types'

export default function MemberSavings() {
  const { t } = useTranslation()
  const toast = useToast()
  const memberId = useMemberId()
  const [open, setOpen] = useState(false)

  const account = savingsAccounts.find((a) => a.memberId === memberId)
  const txns = savingsTransactions
    .filter((s) => s.memberId === memberId)
    .sort((a, b) => +new Date(a.date) - +new Date(b.date))
  const deposits = sum(txns.filter((s) => s.type === 'deposit').map((s) => s.amount))
  const withdrawals = sum(txns.filter((s) => s.type === 'withdrawal').map((s) => s.amount))
  const series = txns.map((s) => ({ month: formatDate(s.date, 'short'), balance: s.balanceAfter }))

  const columns: Column<SavingsTransaction>[] = [
    { key: 'date', header: t('common.date'), sortValue: (s) => s.date, render: (s) => formatDate(s.date) },
    { key: 'type', header: t('common.type'), render: (s) => <Badge tone={s.type === 'deposit' ? 'success' : 'warning'}>{t(`savings.txnType.${s.type}`)}</Badge> },
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.reference}</span> },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (s) => <span className={s.type === 'deposit' ? 'font-medium text-tertiary-700' : 'font-medium text-danger'}>{s.type === 'deposit' ? '+' : '−'}{formatMoney(s.amount)}</span> },
    { key: 'after', header: t('savings.balanceAfter'), align: 'right', render: (s) => <span className="font-semibold">{formatMoney(s.balanceAfter)}</span> },
  ]

  return (
    <>
      <PageHeader
        title={t('nav.mySavings')}
        subtitle={account?.accountNumber}
        actions={<Button leftIcon={<ArrowDownToLine className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('member.makeDeposit')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} tone="secondary" label={t('common.balance')} value={formatMoney(account?.balance ?? 0)} />
        <StatCard index={1} tone="tertiary" label={t('savings.totalDeposits')} value={formatMoney(deposits, { compact: true })} />
        <StatCard index={2} tone="neutral" label={t('savings.totalWithdrawals')} value={formatMoney(withdrawals, { compact: true })} />
      </div>

      <Card className="mt-4">
        <CardBody className="pt-3">
          <AreaTrend data={series.length > 1 ? series : [...series, ...series]} keys={['balance']} colors={['#2563eb']} />
        </CardBody>
      </Card>

      <Card className="mt-4">
        <CardHeader title={t('nav.transactions')} />
        <DataTable columns={columns} rows={[...txns].reverse()} rowKey={(s) => s.id} empty={{ title: t('common.noData') }} />
      </Card>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('member.makeDeposit')}
        description={t('savings.subtitle')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('common.submit') + ' ✓') }}>{t('common.submit')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.amount')}><Input type="number" placeholder="0" /></Field>
          <Field label={t('payments.method')}>
            <select className="h-10 w-full rounded-xl border border-neutral-300 px-3 text-sm">
              <option>{t('payments.methods.mobile_money')}</option>
              <option>{t('payments.methods.bank')}</option>
            </select>
          </Field>
        </div>
      </Modal>
    </>
  )
}
