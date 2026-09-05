import { useTranslation } from 'react-i18next'
import { PlusCircle } from 'lucide-react'
import { Button, Card, CardHeader, DataTable, PageHeader, StatCard } from '@/components/ui'
import type { Column } from '@/components/ui'
import { useToast } from '@/components/ui'
import { formatDate, formatMoney, formatNumber } from '@/lib/format'
import { shares } from '@/mock/data'
import { sum } from '@/mock/selectors'
import { useMemberId } from './useMember'
import type { Share } from '@/types'

export default function MemberShares() {
  const { t } = useTranslation()
  const toast = useToast()
  const memberId = useMemberId()
  const rows = shares.filter((s) => s.memberId === memberId)
  const totalValue = sum(rows.map((s) => s.totalValue))
  const totalQty = sum(rows.map((s) => s.quantity))

  const columns: Column<Share>[] = [
    { key: 'ref', header: t('common.reference'), render: (s) => <span className="font-mono text-[12px] text-neutral-500">{s.transactionRef}</span> },
    { key: 'qty', header: t('shares.quantity'), align: 'right', render: (s) => formatNumber(s.quantity) },
    { key: 'price', header: t('shares.pricePerShare'), align: 'right', render: (s) => formatMoney(s.pricePerShare) },
    { key: 'value', header: t('shares.totalValue'), align: 'right', render: (s) => <span className="font-medium">{formatMoney(s.totalValue)}</span> },
    { key: 'date', header: t('shares.purchasedAt'), sortValue: (s) => s.purchasedAt, render: (s) => formatDate(s.purchasedAt) },
  ]

  return (
    <>
      <PageHeader
        title={t('nav.myShares')}
        subtitle={t('shares.subtitle')}
        actions={<Button leftIcon={<PlusCircle className="h-4 w-4" />} onClick={() => toast(t('member.buyShares') + ' — ' + t('common.submit') + ' ✓')}>{t('member.buyShares')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-2">
        <StatCard index={0} label={t('shares.sharesHeld')} value={formatNumber(totalQty)} />
        <StatCard index={1} tone="tertiary" label={t('member.shareValue')} value={formatMoney(totalValue)} />
      </div>

      <Card className="mt-4">
        <CardHeader title={t('nav.myShares')} />
        <DataTable columns={columns} rows={rows} rowKey={(s) => s.id} empty={{ title: t('common.noData') }} />
      </Card>
    </>
  )
}
