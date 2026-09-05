import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Plus } from 'lucide-react'
import {
  Button, Card, CardBody, CardHeader, DataTable, PageHeader, Progress, SkeletonTable, StatCard,
  StatusBadge, Tabs,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { DonutChart } from '@/components/charts'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDate, formatMoney } from '@/lib/format'
import { loans } from '@/mock/data'
import { loanStatusBreakdown, sum } from '@/mock/selectors'
import type { Loan, LoanStatus } from '@/types'

const TAB_FILTERS: Record<string, LoanStatus[] | null> = {
  all: null,
  applications: ['draft', 'submitted', 'under_review'],
  active: ['approved', 'disbursed', 'active'],
  overdue: ['overdue', 'defaulted'],
  completed: ['completed', 'rejected', 'cancelled'],
}

export default function LoansPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { data, loading } = useMockQuery(() => loans, [])
  const [tab, setTab] = useState('all')
  const [search, setSearch] = useState('')

  const rows = useMemo(() => {
    const allowed = TAB_FILTERS[tab]
    return (data ?? []).filter((l) => {
      if (allowed && !allowed.includes(l.status)) return false
      if (search && !`${l.loanNumber} ${l.productName} ${l.purpose}`.toLowerCase().includes(search.toLowerCase())) return false
      return true
    })
  }, [data, tab, search])

  const disbursed = sum(loans.filter((l) => l.disbursementDate).map((l) => l.principal))
  const outstanding = sum(loans.map((l) => l.outstanding))
  const overdueAmt = sum(loans.filter((l) => l.status === 'overdue' || l.status === 'defaulted').map((l) => l.outstanding))

  const count = (key: string) => {
    const allowed = TAB_FILTERS[key]
    return (data ?? []).filter((l) => !allowed || allowed.includes(l.status)).length
  }

  const columns: Column<Loan>[] = [
    { key: 'no', header: t('loans.loanNumber'), sortValue: (l) => l.loanNumber, render: (l) => <span className="font-medium text-primary-700">{l.loanNumber}</span> },
    { key: 'member', header: t('common.member'), render: (l) => <MemberCell memberId={l.memberId} /> },
    { key: 'product', header: t('loans.product'), render: (l) => <span className="text-[13px]">{l.productName}</span> },
    { key: 'principal', header: t('loans.principal'), align: 'right', sortValue: (l) => l.principal, render: (l) => formatMoney(l.principal) },
    { key: 'progress', header: t('loans.amountPaid'), render: (l) => <div className="w-28"><Progress value={l.total ? (l.amountPaid / l.total) * 100 : 0} showLabel tone={l.status === 'overdue' ? 'danger' : 'primary'} /></div> },
    { key: 'outstanding', header: t('loans.outstanding'), align: 'right', sortValue: (l) => l.outstanding, render: (l) => <span className="font-medium">{formatMoney(l.outstanding)}</span> },
    { key: 'date', header: t('loans.applicationDate'), sortValue: (l) => l.applicationDate, render: (l) => <span className="text-[13px] text-neutral-500">{formatDate(l.applicationDate)}</span> },
    { key: 'status', header: t('common.status'), render: (l) => <StatusBadge status={l.status} label={t(`loans.status.${l.status}`)} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('loans.title')}
        subtitle={t('loans.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => navigate('/admin/loan-products')}>{t('nav.loanProducts')}</Button>}
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_280px]">
        <StatCard index={0} label={t('dashboard.totalLoans')} value={formatMoney(disbursed, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('dashboard.outstandingLoans')} value={formatMoney(outstanding, { compact: true })} />
        <StatCard index={2} tone="neutral" label={t('nav.overdue')} value={formatMoney(overdueAmt, { compact: true })} />
        <Card className="row-span-1">
          <CardHeader title={t('dashboard.loanStatus')} className="!py-3" />
          <CardBody className="!p-2"><DonutChart data={loanStatusBreakdown().map((s) => ({ ...s, status: t(`loans.status.${s.status}`) }))} /></CardBody>
        </Card>
      </div>

      <Card className="mt-4">
        <div className="px-3 pt-2">
          <Tabs
            value={tab}
            onChange={setTab}
            items={[
              { key: 'all', label: t('common.all'), count: count('all') },
              { key: 'applications', label: t('nav.applications'), count: count('applications') },
              { key: 'active', label: t('nav.active'), count: count('active') },
              { key: 'overdue', label: t('nav.overdue'), count: count('overdue') },
              { key: 'completed', label: t('nav.completed'), count: count('completed') },
            ]}
          />
        </div>
        <div className="p-3">
          <ListToolbar search={search} onSearch={setSearch} />
        </div>
        {loading ? <SkeletonTable /> : <DataTable columns={columns} rows={rows} rowKey={(l) => l.id} onRowClick={(l) => navigate(`/admin/loans/${l.id}`)} />}
      </Card>
    </>
  )
}
