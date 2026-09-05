import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Card, DataTable, PageHeader, StatusBadge } from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { formatDateTime, formatMoney } from '@/lib/format'
import { memberTransactions } from '@/mock/selectors'
import { useMemberId } from './useMember'
import type { Transaction } from '@/types'

export default function MemberTransactions() {
  const { t } = useTranslation()
  const memberId = useMemberId()
  const [search, setSearch] = useState('')
  const [type, setType] = useState('all')

  const all = memberTransactions(memberId)
  const types = [...new Set(all.map((x) => x.type))]

  const rows = useMemo(
    () =>
      all.filter((x) => {
        if (type !== 'all' && x.type !== type) return false
        return `${x.reference} ${x.type} ${x.description}`.toLowerCase().includes(search.toLowerCase())
      }),
    [all, search, type],
  )

  const columns: Column<Transaction>[] = [
    { key: 'ref', header: t('transactions.txnRef'), render: (r) => <span className="font-mono text-[12px] text-neutral-500">{r.reference}</span> },
    { key: 'type', header: t('common.type'), render: (r) => t(`transactions.types.${r.type}`) },
    { key: 'amount', header: t('common.amount'), align: 'right', sortValue: (r) => r.amount, render: (r) => <span className="font-medium">{formatMoney(r.amount)}</span> },
    { key: 'date', header: t('common.date'), sortValue: (r) => r.createdAt, render: (r) => <span className="text-[13px] text-neutral-500">{formatDateTime(r.createdAt)}</span> },
    { key: 'status', header: t('common.status'), render: (r) => <StatusBadge status={r.status} /> },
  ]

  return (
    <>
      <PageHeader title={t('nav.transactions')} subtitle={t('transactions.subtitle')} />
      <ListToolbar
        search={search}
        onSearch={setSearch}
        filters={[
          {
            value: type,
            onChange: setType,
            options: [{ value: 'all', label: t('common.all') }, ...types.map((x) => ({ value: x, label: t(`transactions.types.${x}`) }))],
          },
        ]}
      />
      <Card>
        <DataTable columns={columns} rows={rows} rowKey={(r) => r.id} pageSize={15} />
      </Card>
    </>
  )
}
