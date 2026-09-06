import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Download, UserPlus } from 'lucide-react'
import { Button, Card, DataTable, PageHeader, SkeletonTable, StatusBadge, EmptyState, Avatar } from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { useApiQuery } from '@/lib/useApi'
import { listMembers } from '@/api'
import { formatDate, formatMoney } from '@/lib/format'

interface Row {
  id: string
  memberNumber: string
  fullName: string
  phone: string
  email: string
  gender: string
  status: string
  avatarColor?: string
  registrationDate: string
  sharesValue: number
  savingsBalance: number
}

export default function MembersPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')
  const [gender, setGender] = useState('all')

  const query = useMemo(
    () => ({
      search: search || undefined,
      status: status === 'all' ? undefined : status,
      gender: gender === 'all' ? undefined : gender,
      per_page: 100,
    }),
    [search, status, gender],
  )
  const { data, loading, error, refetch } = useApiQuery(() => listMembers(query), [query])
  const rows: Row[] = data?.data ?? []

  const columns: Column<Row>[] = [
    {
      key: 'name', header: t('members.fullName'), sortValue: (m) => m.fullName,
      render: (m) => (
        <span className="flex items-center gap-2.5">
          <Avatar name={m.fullName} color={m.avatarColor} size="sm" />
          <span className="min-w-0">
            <span className="block truncate text-[13.5px] font-medium text-neutral-800">{m.fullName}</span>
            <span className="block text-[11px] text-neutral-400">{m.memberNumber}</span>
          </span>
        </span>
      ),
    },
    { key: 'phone', header: t('common.phone'), render: (m) => <span className="text-[13px] text-neutral-500">{m.phone}</span> },
    { key: 'shares', header: t('nav.shares'), align: 'right', sortValue: (m) => m.sharesValue, render: (m) => formatMoney(m.sharesValue, { compact: true }) },
    { key: 'savings', header: t('nav.savings'), align: 'right', sortValue: (m) => m.savingsBalance, render: (m) => formatMoney(m.savingsBalance, { compact: true }) },
    { key: 'joined', header: t('members.joined'), sortValue: (m) => m.registrationDate, render: (m) => <span className="text-[13px] text-neutral-500">{formatDate(m.registrationDate)}</span> },
    { key: 'status', header: t('common.status'), render: (m) => <StatusBadge status={m.status} label={t(`members.status.${m.status}`)} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('members.title')}
        subtitle={t('members.subtitle')}
        actions={
          <>
            <Button variant="outlined" leftIcon={<Download className="h-4 w-4" />}>{t('common.export')}</Button>
            <Button leftIcon={<UserPlus className="h-4 w-4" />} onClick={() => navigate('/admin/members/new')}>
              {t('members.addMember')}
            </Button>
          </>
        }
      />

      <ListToolbar
        search={search}
        onSearch={setSearch}
        filters={[
          {
            value: status,
            onChange: setStatus,
            options: [
              { value: 'all', label: t('common.all') + ' — ' + t('common.status') },
              ...(['active', 'pending', 'suspended', 'inactive', 'deceased'] as const).map((s) => ({ value: s, label: t(`members.status.${s}`) })),
            ],
          },
          {
            value: gender,
            onChange: setGender,
            options: [
              { value: 'all', label: t('common.all') + ' — ' + t('members.gender') },
              { value: 'male', label: t('members.male') },
              { value: 'female', label: t('members.female') },
            ],
          },
        ]}
      />

      <Card>
        {error ? (
          <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
        ) : loading ? (
          <SkeletonTable />
        ) : (
          <DataTable
            columns={columns}
            rows={rows}
            rowKey={(m) => m.id}
            onRowClick={(m) => navigate(`/admin/members/${m.id}`)}
            empty={{ title: t('common.noData'), hint: t('common.noDataHint') }}
          />
        )}
      </Card>
    </>
  )
}
