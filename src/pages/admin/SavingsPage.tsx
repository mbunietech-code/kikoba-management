import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowDownToLine, ArrowUpFromLine } from 'lucide-react'
import {
  Button, Card, DataTable, Field, Input, Modal, PageHeader, Select, SkeletonTable, StatCard, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDate, formatMoney } from '@/lib/format'
import { activeMembers, savingsAccounts, savingsTransactions } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { SavingsAccount } from '@/types'

export default function SavingsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { data, loading } = useMockQuery(() => savingsAccounts, [])
  const [search, setSearch] = useState('')
  const [modal, setModal] = useState<null | 'deposit' | 'withdraw'>(null)

  const rows = useMemo(
    () =>
      (data ?? []).filter((a) => {
        const name = activeMembers.find((m) => m.id === a.memberId)?.fullName ?? ''
        return `${name} ${a.accountNumber}`.toLowerCase().includes(search.toLowerCase())
      }),
    [data, search],
  )

  const deposits = sum(savingsTransactions.filter((s) => s.type === 'deposit').map((s) => s.amount))
  const withdrawals = sum(savingsTransactions.filter((s) => s.type === 'withdrawal').map((s) => s.amount))

  const columns: Column<SavingsAccount>[] = [
    { key: 'member', header: t('common.member'), render: (a) => <MemberCell memberId={a.memberId} /> },
    { key: 'acc', header: t('savings.accountNumber'), render: (a) => <span className="font-mono text-[12px] text-neutral-500">{a.accountNumber}</span> },
    { key: 'opened', header: t('common.created'), sortValue: (a) => a.openedAt, render: (a) => <span className="text-[13px] text-neutral-500">{formatDate(a.openedAt)}</span> },
    { key: 'balance', header: t('common.balance'), align: 'right', sortValue: (a) => a.balance, render: (a) => <span className="font-semibold">{formatMoney(a.balance)}</span> },
  ]

  return (
    <>
      <PageHeader
        title={t('savings.title')}
        subtitle={t('savings.subtitle')}
        actions={
          <>
            <Button variant="outlined" leftIcon={<ArrowUpFromLine className="h-4 w-4" />} onClick={() => setModal('withdraw')}>{t('savings.withdraw')}</Button>
            <Button leftIcon={<ArrowDownToLine className="h-4 w-4" />} onClick={() => setModal('deposit')}>{t('savings.deposit')}</Button>
          </>
        }
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} tone="tertiary" label={t('savings.totalDeposits')} value={formatMoney(deposits, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('savings.totalWithdrawals')} value={formatMoney(withdrawals, { compact: true })} />
        <StatCard index={2} tone="secondary" label={t('savings.netSavings')} value={formatMoney(deposits - withdrawals, { compact: true })} />
      </div>

      <div className="mt-4">
        <ListToolbar search={search} onSearch={setSearch} />
        <Card>
          {loading ? <SkeletonTable /> : <DataTable columns={columns} rows={rows} rowKey={(a) => a.id} onRowClick={(a) => navigate(`/admin/savings/${a.id}`)} />}
        </Card>
      </div>

      <Modal
        open={modal !== null}
        onClose={() => setModal(null)}
        title={modal === 'withdraw' ? t('savings.recordWithdrawal') : t('savings.recordDeposit')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setModal(null)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setModal(null); toast(t('common.save') + ' ✓') }}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.member')}>
            <Select>{activeMembers.map((m) => <option key={m.id} value={m.id}>{m.fullName} — {m.memberNumber}</option>)}</Select>
          </Field>
          <Field label={t('common.amount')}><Input type="number" placeholder="0" /></Field>
          <Field label={t('common.date')}><Input type="date" defaultValue={new Date().toISOString().slice(0, 10)} /></Field>
          <Field label={t('common.reference')} hint={t('common.optional')}><Input placeholder="MPESA / Bank ref" /></Field>
        </div>
      </Modal>
    </>
  )
}
