import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowDownToLine, ArrowUpFromLine } from 'lucide-react'
import {
  Avatar, Button, Card, DataTable, Field, Input, Modal, PageHeader, Select, SkeletonTable, StatCard,
  useToast, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { useApiQuery } from '@/lib/useApi'
import { deposit, listMembers, listSavings, savingsSummary, withdraw } from '@/api'
import { formatDate, formatMoney } from '@/lib/format'

interface AccRow {
  id: string
  member?: { fullName: string; memberNumber: string; avatarColor?: string }
  accountNumber: string
  balance: number
  status: string
  openedAt: string
}

export default function SavingsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const [search, setSearch] = useState('')
  const [modal, setModal] = useState<null | 'deposit' | 'withdraw'>(null)
  const [form, setForm] = useState({ account_id: '', amount: '', reference: '' })
  const [busy, setBusy] = useState(false)

  const query = useMemo(() => ({ search: search || undefined, per_page: 100 }), [search])
  const { data, loading, error, refetch } = useApiQuery(() => listSavings(query), [query])
  const { data: summary, refetch: refetchSummary } = useApiQuery(() => savingsSummary(), [])
  const { data: members } = useApiQuery(() => listMembers({ status: 'active', per_page: 100 }), [])
  const rows: AccRow[] = data?.data ?? []

  async function submit() {
    setBusy(true)
    try {
      const body = { account_id: form.account_id, amount: Number(form.amount), reference: form.reference || undefined }
      await (modal === 'withdraw' ? withdraw(body) : deposit(body))
      toast(t('common.save') + ' ✓')
      setModal(null)
      setForm({ account_id: '', amount: '', reference: '' })
      refetch()
      refetchSummary()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  const columns: Column<AccRow>[] = [
    {
      key: 'member', header: t('common.member'),
      render: (a) => a.member ? (
        <span className="flex items-center gap-2.5">
          <Avatar name={a.member.fullName} color={a.member.avatarColor} size="sm" />
          <span>
            <span className="block text-[13px] font-medium text-neutral-800">{a.member.fullName}</span>
            <span className="block text-[11px] text-neutral-400">{a.member.memberNumber}</span>
          </span>
        </span>
      ) : '—',
    },
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
        <StatCard index={0} tone="tertiary" label={t('savings.totalDeposits')} value={formatMoney(summary?.totalDeposits ?? 0, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('savings.totalWithdrawals')} value={formatMoney(summary?.totalWithdrawals ?? 0, { compact: true })} />
        <StatCard index={2} tone="secondary" label={t('savings.netSavings')} value={formatMoney(summary?.netSavings ?? 0, { compact: true })} />
      </div>

      <div className="mt-4">
        <ListToolbar search={search} onSearch={setSearch} />
        <Card>
          {error ? (
            <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
          ) : loading ? (
            <SkeletonTable />
          ) : (
            <DataTable columns={columns} rows={rows} rowKey={(a) => a.id} onRowClick={(a) => navigate(`/admin/savings/${a.id}`)} />
          )}
        </Card>
      </div>

      <Modal
        open={modal !== null}
        onClose={() => setModal(null)}
        title={modal === 'withdraw' ? t('savings.recordWithdrawal') : t('savings.recordDeposit')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setModal(null)}>{t('common.cancel')}</Button>
            <Button loading={busy} onClick={submit}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('savings.accountNumber')}>
            <Select value={form.account_id} onChange={(e) => setForm((f) => ({ ...f, account_id: e.target.value }))}>
              <option value="">—</option>
              {rows.map((a) => <option key={a.id} value={a.id}>{a.member?.fullName} · {a.accountNumber}</option>)}
            </Select>
          </Field>
          <Field label={t('common.amount')}>
            <Input type="number" value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} placeholder="0" />
          </Field>
          <Field label={t('common.reference')} hint={t('common.optional')}>
            <Input value={form.reference} onChange={(e) => setForm((f) => ({ ...f, reference: e.target.value }))} placeholder="MPESA / Bank ref" />
          </Field>
          {!members && <p className="text-[12px] text-neutral-400">…</p>}
        </div>
      </Modal>
    </>
  )
}
