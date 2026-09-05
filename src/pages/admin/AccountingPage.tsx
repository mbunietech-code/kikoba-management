import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Badge, Card, DataTable, PageHeader, StatCard, Tabs } from '@/components/ui'
import type { Column, Tone } from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { accounts, journalEntries } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { Account, JournalEntry } from '@/types'

const typeTone: Record<string, Tone> = {
  asset: 'primary', liability: 'info', equity: 'purple', revenue: 'success', expense: 'danger',
}

export default function AccountingPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [tab, setTab] = useState('coa')

  const totalDebit = sum(journalEntries.flatMap((j) => j.lines.map((l) => l.debit)))
  const totalCredit = sum(journalEntries.flatMap((j) => j.lines.map((l) => l.credit)))
  const assets = sum(accounts.filter((a) => a.type === 'asset').map((a) => a.balance))
  const liabilities = sum(accounts.filter((a) => a.type === 'liability').map((a) => a.balance))

  const coaCols: Column<Account>[] = [
    { key: 'code', header: t('accounting.accountCode'), sortValue: (a) => a.code, render: (a) => <span className="font-mono text-[13px] text-neutral-600">{a.code}</span> },
    { key: 'name', header: t('accounting.accountName'), render: (a) => <span className="font-medium text-neutral-800">{a.name}</span> },
    { key: 'type', header: t('accounting.accountType'), render: (a) => <Badge tone={typeTone[a.type]}>{t(`accounting.types.${a.type}`)}</Badge> },
    { key: 'balance', header: t('common.balance'), align: 'right', sortValue: (a) => a.balance, render: (a) => <span className="font-medium tabular-nums">{formatMoney(a.balance)}</span> },
  ]
  const jeCols: Column<JournalEntry>[] = [
    { key: 'ref', header: t('common.reference'), render: (j) => <span className="font-mono text-[12px] text-primary-700">{j.reference}</span> },
    { key: 'desc', header: t('common.description'), render: (j) => j.description },
    { key: 'date', header: t('accounting.entryDate'), sortValue: (j) => j.entryDate, render: (j) => formatDate(j.entryDate) },
    { key: 'by', header: t('accounting.postedBy'), render: (j) => <span className="text-[13px] text-neutral-500">{j.postedBy}</span> },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (j) => <span className="font-medium">{formatMoney(sum(j.lines.map((l) => l.debit)))}</span> },
  ]

  return (
    <>
      <PageHeader title={t('accounting.title')} subtitle={t('accounting.subtitle')} />

      <div className="grid gap-4 sm:grid-cols-4">
        <StatCard index={0} label={t('accounting.types.asset')} value={formatMoney(assets, { compact: true })} />
        <StatCard index={1} tone="secondary" label={t('accounting.types.liability')} value={formatMoney(liabilities, { compact: true })} />
        <StatCard index={2} tone="tertiary" label={t('accounting.totalDebit')} value={formatMoney(totalDebit, { compact: true })} />
        <StatCard index={3} tone="neutral" label={t('accounting.totalCredit')} value={formatMoney(totalCredit, { compact: true })} hint={totalDebit === totalCredit ? t('accounting.balanced') : t('accounting.unbalanced')} />
      </div>

      <Card className="mt-4">
        <div className="px-3 pt-2">
          <Tabs
            value={tab}
            onChange={setTab}
            items={[
              { key: 'coa', label: t('accounting.chartOfAccounts'), count: accounts.length },
              { key: 'journal', label: t('accounting.journalEntries'), count: journalEntries.length },
              { key: 'trial', label: t('accounting.trialBalance') },
            ]}
          />
        </div>

        {tab === 'coa' && <DataTable columns={coaCols} rows={accounts} rowKey={(a) => a.id} pageSize={20} />}
        {tab === 'journal' && <DataTable columns={jeCols} rows={journalEntries} rowKey={(j) => j.id} onRowClick={(j) => navigate(`/admin/accounting/journal/${j.id}`)} />}
        {tab === 'trial' && (
          <div className="p-5">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-neutral-200 text-left text-[12px] uppercase tracking-wide text-neutral-500">
                  <th className="py-2">{t('accounting.accountName')}</th>
                  <th className="py-2 text-right">{t('accounting.debit')}</th>
                  <th className="py-2 text-right">{t('accounting.credit')}</th>
                </tr>
              </thead>
              <tbody>
                {accounts.map((a) => {
                  const isDebit = a.type === 'asset' || a.type === 'expense'
                  return (
                    <tr key={a.id} className="border-b border-neutral-100">
                      <td className="py-2.5">{a.code} · {a.name}</td>
                      <td className="py-2.5 text-right tabular-nums">{isDebit ? formatMoney(a.balance) : '—'}</td>
                      <td className="py-2.5 text-right tabular-nums">{!isDebit ? formatMoney(a.balance) : '—'}</td>
                    </tr>
                  )
                })}
                <tr className="font-semibold">
                  <td className="py-3">{t('common.total')}</td>
                  <td className="py-3 text-right tabular-nums">{formatMoney(sum(accounts.filter((a) => a.type === 'asset' || a.type === 'expense').map((a) => a.balance)))}</td>
                  <td className="py-3 text-right tabular-nums">{formatMoney(sum(accounts.filter((a) => a.type !== 'asset' && a.type !== 'expense').map((a) => a.balance)))}</td>
                </tr>
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </>
  )
}
