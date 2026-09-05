import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { UserPlus } from 'lucide-react'
import {
  Avatar, Badge, Button, Card, DataTable, Field, Input, Modal, PageHeader, Select, StatusBadge, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { relativeTime } from '@/lib/format'
import { staffUsers } from '@/mock/data'
import type { StaffUser } from '@/types'

export default function UsersPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [open, setOpen] = useState(false)

  const columns: Column<StaffUser>[] = [
    {
      key: 'name', header: t('common.name'), sortValue: (u) => u.name,
      render: (u) => (
        <span className="flex items-center gap-2.5">
          <Avatar name={u.name} size="sm" color="#0f172a" />
          <span>
            <span className="block text-[13.5px] font-medium text-neutral-800">{u.name}</span>
            <span className="block text-[11px] text-neutral-400">{u.email}</span>
          </span>
        </span>
      ),
    },
    { key: 'phone', header: t('common.phone'), render: (u) => <span className="text-[13px] text-neutral-500">{u.phone}</span> },
    { key: 'role', header: t('users.role'), render: (u) => <Badge tone="primary">{t(`users.roles.${u.role}`)}</Badge> },
    { key: 'login', header: t('users.lastLogin'), sortValue: (u) => u.lastLoginAt ?? '', render: (u) => <span className="text-[13px] text-neutral-500">{u.lastLoginAt ? relativeTime(u.lastLoginAt) : t('users.never')}</span> },
    { key: 'status', header: t('common.status'), render: (u) => <StatusBadge status={u.status} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('users.title')}
        subtitle={t('users.subtitle')}
        actions={<Button leftIcon={<UserPlus className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('users.addUser')}</Button>}
      />

      <Card>
        <DataTable columns={columns} rows={staffUsers} rowKey={(u) => u.id} />
      </Card>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('users.addUser')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('common.create') + ' ✓') }}>{t('common.create')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.name')}><Input placeholder="Full name" /></Field>
          <Field label={t('common.email')}><Input type="email" /></Field>
          <Field label={t('common.phone')}><Input /></Field>
          <Field label={t('users.role')}>
            <Select>
              {(['admin', 'treasurer', 'accountant', 'loan_officer'] as const).map((r) => (
                <option key={r} value={r}>{t(`users.roles.${r}`)}</option>
              ))}
            </Select>
          </Field>
        </div>
      </Modal>
    </>
  )
}
