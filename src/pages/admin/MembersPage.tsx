import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Download, UserPlus } from 'lucide-react'
import {
  Button, Card, DataTable, PageHeader, SkeletonTable, StatusBadge,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDate, formatMoney } from '@/lib/format'
import { members, savingsAccounts, shares } from '@/mock/data'
import type { Member } from '@/types'

export default function MembersPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { data, loading } = useMockQuery(() => members, [])
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')
  const [gender, setGender] = useState('all')

  const rows = useMemo(() => {
    return (data ?? []).filter((m) => {
      if (status !== 'all' && m.status !== status) return false
      if (gender !== 'all' && m.gender !== gender) return false
      if (search && !`${m.fullName} ${m.memberNumber} ${m.phone} ${m.email}`.toLowerCase().includes(search.toLowerCase())) return false
      return true
    })
  }, [data, search, status, gender])

  const shareValue = (id: string) => shares.filter((s) => s.memberId === id).reduce((a, s) => a + s.totalValue, 0)
  const savingsBal = (id: string) => savingsAccounts.find((a) => a.memberId === id)?.balance ?? 0

  const columns: Column<Member>[] = [
    { key: 'name', header: t('members.fullName'), sortValue: (m) => m.fullName, render: (m) => <MemberCell memberId={m.id} /> },
    { key: 'phone', header: t('common.phone'), render: (m) => <span className="text-[13px] text-neutral-500">{m.phone}</span> },
    { key: 'shares', header: t('nav.shares'), align: 'right', sortValue: (m) => shareValue(m.id), render: (m) => formatMoney(shareValue(m.id), { compact: true }) },
    { key: 'savings', header: t('nav.savings'), align: 'right', sortValue: (m) => savingsBal(m.id), render: (m) => formatMoney(savingsBal(m.id), { compact: true }) },
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
        {loading ? (
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
