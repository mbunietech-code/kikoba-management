import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Plus } from 'lucide-react'
import {
  Button, Card, CardBody, CardHeader, DataTable, PageHeader, Progress, SkeletonTable, StatCard,
  StatusBadge, Tabs, EmptyState, Avatar,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { DonutChart } from '@/components/charts'
import { useApiQuery } from '@/lib/useApi'
import { listLoans, loansSummary } from '@/api'
import { formatDate, formatMoney } from '@/lib/format'

interface LoanRow {
  id: string
  loanNumber: string
  member?: { fullName: string; memberNumber: string; avatarColor?: string }
  productName: string
  principal: number
  total: number
  amountPaid: number
  outstanding: number
  status: string
  applicationDate: string
}

export default function LoansPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [tab, setTab] = useState('all')
  const [search, setSearch] = useState('')

  const query = useMemo(
    () => ({ tab: tab === 'all' ? undefined : tab, search: search || undefined, per_page: 100 }),
    [tab, search],
  )
  const { data, loading, error, refetch } = useApiQuery(() => listLoans(query), [query])
  const { data: summary } = useApiQuery(() => loansSummary(), [])
  const rows: LoanRow[] = data?.data ?? []

  const columns: Column<LoanRow>[] = [
    { key: 'no', header: t('loans.loanNumber'), sortValue: (l) => l.loanNumber, render: (l) => <span className="font-medium text-primary-700">{l.loanNumber}</span> },
    {
      key: 'member', header: t('common.member'),
      render: (l) => l.member ? (
        <span className="flex items-center gap-2">
          <Avatar name={l.member.fullName} color={l.member.avatarColor} size="xs" />
          <span className="text-[13px]">{l.member.fullName}</span>
        </span>
      ) : '—',
    },
    { key: 'product', header: t('loans.product'), render: (l) => <span className="text-[13px]">{l.productName}</span> },
    { key: 'principal', header: t('loans.principal'), align: 'right', sortValue: (l) => l.principal, render: (l) => formatMoney(l.principal) },
    { key: 'progress', header: t('loans.amountPaid'), render: (l) => <div className="w-28"><Progress value={l.total ? (l.amountPaid / l.total) * 100 : 0} showLabel tone={l.status === 'overdue' ? 'danger' : 'primary'} /></div> },
    { key: 'outstanding', header: t('loans.outstanding'), align: 'right', sortValue: (l) => l.outstanding, render: (l) => <span className="font-medium">{formatMoney(l.outstanding)}</span> },
    { key: 'date', header: t('loans.applicationDate'), sortValue: (l) => l.applicationDate, render: (l) => <span className="text-[13px] text-neutral-500">{formatDate(l.applicationDate)}</span> },
    { key: 'status', header: t('common.status'), render: (l) => <StatusBadge status={l.status} label={t(`loans.status.${l.status}`)} /> },
  ]

  const statusEntries = Object.entries(summary?.byStatus ?? {}) as [string, number][]

  return (
    <>
      <PageHeader
        title={t('loans.title')}
        subtitle={t('loans.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => navigate('/admin/loan-products')}>{t('nav.loanProducts')}</Button>}
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_280px]">
        <StatCard index={0} label={t('dashboard.totalLoans')} value={formatMoney(summary?.disbursed ?? 0, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('dashboard.outstandingLoans')} value={formatMoney(summary?.outstanding ?? 0, { compact: true })} />
        <StatCard index={2} tone="neutral" label={t('nav.overdue')} value={formatMoney(summary?.overdue ?? 0, { compact: true })} />
        <Card>
          <CardHeader title={t('dashboard.loanStatus')} className="!py-3" />
          <CardBody className="!p-2"><DonutChart data={statusEntries.map(([status, count]) => ({ status: t(`loans.status.${status}`), count }))} /></CardBody>
        </Card>
      </div>

      <Card className="mt-4">
        <div className="px-3 pt-2">
          <Tabs
            value={tab}
            onChange={setTab}
            items={[
              { key: 'all', label: t('common.all') },
              { key: 'applications', label: t('nav.applications') },
              { key: 'active', label: t('nav.active') },
              { key: 'overdue', label: t('nav.overdue') },
              { key: 'completed', label: t('nav.completed') },
            ]}
          />
        </div>
        <div className="p-3"><ListToolbar search={search} onSearch={setSearch} /></div>
        {error ? (
          <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
        ) : loading ? (
          <SkeletonTable />
        ) : (
          <DataTable columns={columns} rows={rows} rowKey={(l) => l.id} onRowClick={(l) => navigate(`/admin/loans/${l.id}`)} />
        )}
      </Card>
    </>
  )
}
