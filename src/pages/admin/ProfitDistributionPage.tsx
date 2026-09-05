import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus } from 'lucide-react'
import {
  Button, Card, CardHeader, DataTable, Field, Input, Modal, PageHeader,
  Select, StatCard, StatusBadge, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { MemberCell } from '@/components/MemberCell'
import { formatDate, formatMoney, formatPercent } from '@/lib/format'
import { activeMembers, profitDistributions, shares } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { ProfitDistribution } from '@/types'

export default function ProfitDistributionPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [open, setOpen] = useState(false)
  const [selected, setSelected] = useState<ProfitDistribution | null>(profitDistributions[0])

  const totalShares = sum(shares.map((s) => s.totalValue))
  const allocations = selected
    ? activeMembers.map((m) => {
        const memberShareValue = sum(shares.filter((s) => s.memberId === m.id).map((s) => s.totalValue))
        const pct = totalShares ? (memberShareValue / totalShares) * 100 : 0
        return { member: m, pct, amount: Math.round((selected.distributableProfit * pct) / 100) }
      })
    : []

  const distCols: Column<ProfitDistribution>[] = [
    { key: 'period', header: t('reports.period'), render: (d) => `${formatDate(d.periodStart, 'short')} – ${formatDate(d.periodEnd, 'short')}` },
    { key: 'total', header: t('profit.totalProfit'), align: 'right', render: (d) => formatMoney(d.totalProfit) },
    { key: 'reserved', header: t('profit.reservedAmount'), align: 'right', render: (d) => formatMoney(d.reservedAmount) },
    { key: 'dist', header: t('profit.distributableProfit'), align: 'right', render: (d) => <span className="font-medium">{formatMoney(d.distributableProfit)}</span> },
    { key: 'basis', header: t('profit.basis'), render: (d) => t(`profit.basis${d.basis[0].toUpperCase()}${d.basis.slice(1)}` as 'profit.basisShares') },
    { key: 'status', header: t('common.status'), render: (d) => <StatusBadge status={d.status} label={t(`profit.status.${d.status}`)} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('profit.title')}
        subtitle={t('profit.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('profit.newDistribution')}</Button>}
      />

      <Card>
        <CardHeader title={t('profit.title')} />
        <DataTable columns={distCols} rows={profitDistributions} rowKey={(d) => d.id} onRowClick={(d) => setSelected(d)} />
      </Card>

      {selected && (
        <>
          <div className="mt-4 grid gap-4 sm:grid-cols-3">
            <StatCard index={0} label={t('profit.totalProfit')} value={formatMoney(selected.totalProfit, { compact: true })} />
            <StatCard index={1} tone="neutral" label={t('profit.reservedAmount')} value={formatMoney(selected.reservedAmount, { compact: true })} />
            <StatCard index={2} tone="tertiary" label={t('profit.distributableProfit')} value={formatMoney(selected.distributableProfit, { compact: true })} />
          </div>

          <Card className="mt-4">
            <CardHeader
              title={t('profit.allocation')}
              subtitle={`${formatDate(selected.periodStart)} – ${formatDate(selected.periodEnd)} · ${t('profit.basisShares')}`}
            />
            <DataTable
              columns={[
                { key: 'member', header: t('common.member'), render: (a) => <MemberCell memberId={a.member.id} /> },
                { key: 'pct', header: '%', align: 'right', sortValue: (a) => a.pct, render: (a) => formatPercent(a.pct, 2) },
                { key: 'amount', header: t('profit.allocation'), align: 'right', sortValue: (a) => a.amount, render: (a) => <span className="font-medium">{formatMoney(a.amount)}</span> },
              ]}
              rows={allocations}
              rowKey={(a) => a.member.id}
              pageSize={10}
            />
          </Card>
        </>
      )}

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('profit.newDistribution')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('profit.status.calculated') + ' ✓') }}>{t('common.create')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('profit.periodStart')}><Input type="date" /></Field>
            <Field label={t('profit.periodEnd')}><Input type="date" /></Field>
          </div>
          <Field label={t('profit.totalProfit')}><Input type="number" /></Field>
          <Field label={t('settings.reserveRate')}><Input type="number" defaultValue={20} /></Field>
          <Field label={t('profit.basis')}>
            <Select>
              <option value="shares">{t('profit.basisShares')}</option>
              <option value="savings">{t('profit.basisSavings')}</option>
              <option value="equal">{t('profit.basisEqual')}</option>
            </Select>
          </Field>
        </div>
      </Modal>
    </>
  )
}
