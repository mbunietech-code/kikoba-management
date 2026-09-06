import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { UserPlus } from 'lucide-react'
import {
  Avatar, Badge, Button, Card, DataTable, Field, Input, Modal, PageHeader, Select, StatusBadge,
  useToast, EmptyState,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { RowActions } from '@/components/RowActions'
import { useApiQuery } from '@/lib/useApi'
import { createUser, deleteUser, listUsers, updateUser } from '@/api'
import { relativeTime } from '@/lib/format'

const ROLES = ['admin', 'treasurer', 'accountant', 'loan_officer'] as const

export default function UsersPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { data, loading, error, refetch } = useApiQuery(() => listUsers(), [])
  const users: any[] = data ?? []

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<any | null>(null)
  const [form, setForm] = useState<any>({ name: '', email: '', phone: '', role: 'admin', status: 'active' })
  const [busy, setBusy] = useState(false)

  function startCreate() {
    setEditing(null)
    setForm({ name: '', email: '', phone: '', role: 'admin', status: 'active' })
    setOpen(true)
  }
  function startEdit(u: any) {
    setEditing(u)
    setForm({ name: u.name, email: u.email, phone: u.phone ?? '', role: u.role, status: u.status })
    setOpen(true)
  }

  async function save() {
    setBusy(true)
    try {
      if (editing) await updateUser(editing.id, { name: form.name, phone: form.phone, role: form.role, status: form.status })
      else await createUser({ name: form.name, email: form.email, phone: form.phone, role: form.role })
      toast(t('common.save') + ' ✓')
      setOpen(false)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  async function remove(u: any) {
    try {
      await deleteUser(u.id)
      toast(t('common.deleted'))
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  const columns: Column<any>[] = [
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
    { key: 'login', header: t('users.lastLogin'), render: (u) => <span className="text-[13px] text-neutral-500">{u.lastLoginAt ? relativeTime(u.lastLoginAt) : t('users.never')}</span> },
    { key: 'status', header: t('common.status'), render: (u) => <StatusBadge status={u.status} /> },
    {
      key: 'actions', header: '', align: 'right',
      render: (u) => u.role === 'super_admin'
        ? null
        : <RowActions onEdit={() => startEdit(u)} onDelete={() => remove(u)} deleteMessage={t('common.confirmDelete')} />,
    },
  ]

  return (
    <>
      <PageHeader
        title={t('users.title')}
        subtitle={t('users.subtitle')}
        actions={<Button leftIcon={<UserPlus className="h-4 w-4" />} onClick={startCreate}>{t('users.addUser')}</Button>}
      />

      <Card>
        {error ? (
          <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
        ) : loading ? (
          <EmptyState title={t('common.loading')} />
        ) : (
          <DataTable columns={columns} rows={users} rowKey={(u) => u.id} />
        )}
      </Card>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={editing ? `${t('common.edit')} — ${editing.name}` : t('users.addUser')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button loading={busy} onClick={save}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.name')}><Input value={form.name} onChange={(e) => setForm((f: any) => ({ ...f, name: e.target.value }))} /></Field>
          <Field label={t('common.email')}><Input type="email" value={form.email} disabled={!!editing} onChange={(e) => setForm((f: any) => ({ ...f, email: e.target.value }))} /></Field>
          <Field label={t('common.phone')}><Input value={form.phone} onChange={(e) => setForm((f: any) => ({ ...f, phone: e.target.value }))} /></Field>
          <Field label={t('users.role')}>
            <Select value={form.role} onChange={(e) => setForm((f: any) => ({ ...f, role: e.target.value }))}>
              {ROLES.map((r) => <option key={r} value={r}>{t(`users.roles.${r}`)}</option>)}
            </Select>
          </Field>
          {editing && (
            <Field label={t('common.status')}>
              <Select value={form.status} onChange={(e) => setForm((f: any) => ({ ...f, status: e.target.value }))}>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </Select>
            </Field>
          )}
        </div>
      </Modal>
    </>
  )
}
