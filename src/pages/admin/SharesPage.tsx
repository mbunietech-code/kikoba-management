import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus } from 'lucide-react'
import {
  Button, Card, CardBody, CardHeader, DataTable, Field, Input, Modal, PageHeader, Select,
  SkeletonTable, StatCard, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { MiniBars } from '@/components/charts'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDate, formatMoney, formatNumber } from '@/lib/format'
import { activeMembers, shares } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { Share } from '@/types'

export default function SharesPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { data, loading } = useMockQuery(() => shares, [])
  const [search, setSearch] = useState('')
  const [open, setOpen] = useState(false)

  const rows = useMemo(
    () =>
      (data ?? []).filter((s) => {
        const name = activeMembers.find((m) => m.id === s.memberId)?.fullName ?? ''
        return `${name} ${s.transactionRef}`.toLowerCase().includes(search.toLowerCase())
      }),
    [data, search],
  )

  const totalValue = sum(shares.map((s) => s.totalValue))
  const totalQty = sum(shares.map((s) => s.quantity))
  const holders = new Set(shares.map((s) => s.memberId)).size

  const trend = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'].map((m, i) => ({ month: m, amount: 1_800_000 + i * 520_000 }))

  const columns: Column<Share>[] = [
    { key: 'member', header: t('common.member'), render: (s) => <MemberCell memberId={s.memberId} /> },
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.transactionRef}</span> },
    { key: 'qty', header: t('shares.quantity'), align: 'right', sortValue: (s) => s.quantity, render: (s) => formatNumber(s.quantity) },
    { key: 'price', header: t('shares.pricePerShare'), align: 'right', render: (s) => formatMoney(s.pricePerShare) },
    { key: 'value', header: t('shares.totalValue'), align: 'right', sortValue: (s) => s.totalValue, render: (s) => <span className="font-medium">{formatMoney(s.totalValue)}</span> },
    { key: 'date', header: t('shares.purchasedAt'), sortValue: (s) => s.purchasedAt, render: (s) => <span className="text-[13px] text-neutral-500">{formatDate(s.purchasedAt)}</span> },
  ]

  return (
    <>
      <PageHeader
        title={t('shares.title')}
        subtitle={t('shares.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('shares.recordPurchase')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('shares.shareCapital')} value={formatMoney(totalValue, { compact: true })} />
        <StatCard index={1} tone="tertiary" label={t('shares.sharesOutstanding')} value={formatNumber(totalQty)} />
        <StatCard index={2} tone="secondary" label={t('shares.holders')} value={holders} />
      </div>

      <Card className="mt-4">
        <CardHeader title={t('shares.title')} subtitle={t('dashboard.contributionTrend')} />
        <CardBody className="pt-2"><MiniBars data={trend} color="#16a34a" /></CardBody>
      </Card>

      <div className="mt-4">
        <ListToolbar search={search} onSearch={setSearch} />
        <Card>{loading ? <SkeletonTable /> : <DataTable columns={columns} rows={rows} rowKey={(s) => s.id} />}</Card>
      </div>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('shares.recordPurchase')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('shares.recordPurchase') + ' ✓') }}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.member')}>
            <Select>
              {activeMembers.map((m) => <option key={m.id} value={m.id}>{m.fullName} — {m.memberNumber}</option>)}
            </Select>
          </Field>
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('shares.quantity')}><Input type="number" defaultValue={10} /></Field>
            <Field label={t('shares.pricePerShare')}><Input type="number" defaultValue={10000} /></Field>
          </div>
          <Field label={t('shares.purchasedAt')}><Input type="date" defaultValue={new Date().toISOString().slice(0, 10)} /></Field>
        </div>
      </Modal>
    </>
  )
}
