import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Badge, Card, DataTable, PageHeader } from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { formatDateTime } from '@/lib/format'
import { auditLogs } from '@/mock/data'
import type { AuditLog } from '@/types'

const actionTone: Record<string, 'success' | 'danger' | 'info' | 'warning' | 'neutral'> = {
  APPROVE_LOAN: 'success', DISBURSE_LOAN: 'info', CREATE_MEMBER: 'success', RECORD_DEPOSIT: 'success',
  VERIFY_PAYMENT: 'success', UPDATE_SETTINGS: 'warning', REVERSE_TRANSACTION: 'danger', REJECT_CLAIM: 'danger',
}

export default function AuditLogsPage() {
  const { t } = useTranslation()
  const [search, setSearch] = useState('')
  const [action, setAction] = useState('all')

  const actions = [...new Set(auditLogs.map((a) => a.action))]

  const rows = useMemo(
    () =>
      auditLogs.filter((a) => {
        if (action !== 'all' && a.action !== action) return false
        return `${a.user} ${a.action} ${a.entity} ${a.entityId}`.toLowerCase().includes(search.toLowerCase())
      }),
    [search, action],
  )

  const columns: Column<AuditLog>[] = [
    { key: 'time', header: t('audit.timestamp'), sortValue: (a) => a.createdAt, render: (a) => <span className="text-[13px] text-neutral-500">{formatDateTime(a.createdAt)}</span> },
    { key: 'user', header: t('audit.user'), render: (a) => <span className="font-mono text-[12px] text-neutral-600">{a.user}</span> },
    { key: 'action', header: t('audit.action'), render: (a) => <Badge tone={actionTone[a.action] ?? 'neutral'}>{a.action.replace(/_/g, ' ')}</Badge> },
    { key: 'entity', header: t('audit.entity'), render: (a) => <span className="text-[13px]">{a.entity}</span> },
    { key: 'id', header: t('audit.entityId'), render: (a) => <span className="font-mono text-[12px] text-neutral-500">{a.entityId}</span> },
    { key: 'change', header: `${t('audit.oldValue')} → ${t('audit.newValue')}`, render: (a) => (
      <span className="text-[13px]">
        {a.oldValue ? <span className="text-neutral-400 line-through">{a.oldValue}</span> : <span className="text-neutral-300">—</span>}
        <span className="mx-1.5 text-neutral-300">→</span>
        <span className="font-medium text-neutral-700">{a.newValue}</span>
      </span>
    ) },
    { key: 'ip', header: t('audit.ipAddress'), render: (a) => <span className="font-mono text-[12px] text-neutral-400">{a.ipAddress}</span> },
  ]

  return (
    <>
      <PageHeader title={t('audit.title')} subtitle={t('audit.subtitle')} />
      <ListToolbar
        search={search}
        onSearch={setSearch}
        filters={[
          {
            value: action,
            onChange: setAction,
            options: [{ value: 'all', label: t('common.all') + ' — ' + t('audit.action') }, ...actions.map((a) => ({ value: a, label: a.replace(/_/g, ' ') }))],
          },
        ]}
      />
      <Card>
        <DataTable columns={columns} rows={rows} rowKey={(a) => a.id} pageSize={15} />
      </Card>
    </>
  )
}
