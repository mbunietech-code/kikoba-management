import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Plus, Users } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, Field, Input, Modal, PageHeader, Progress, Select, StatCard, useToast, EmptyState,
} from '@/components/ui'
import { RowActions } from '@/components/RowActions'
import { useApiQuery } from '@/lib/useApi'
import { createProject, deleteProject, listProjects, updateProject } from '@/api'
import { formatDate, formatMoney } from '@/lib/format'

const EMPTY = { name: '', description: '', type: 'monthly', capital_required: 0, expected_profit: 0, start_date: '', end_date: '', manager: '', status: 'planned' }

export default function ProjectsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { data, loading, error, refetch } = useApiQuery(() => listProjects(), [])
  const projects: any[] = data ?? []

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<any | null>(null)
  const [form, setForm] = useState<any>(EMPTY)
  const [busy, setBusy] = useState(false)

  const active = projects.filter((p) => p.status === 'active').length
  const capital = projects.reduce((a, p) => a + (p.capitalRaised ?? 0), 0)
  const profit = projects.reduce((a, p) => a + (p.actualProfit ?? 0), 0)

  function startEdit(p: any) {
    setEditing(p)
    setForm({
      name: p.name, description: p.description ?? '', type: p.type, capital_required: p.capitalRequired,
      expected_profit: p.expectedProfit, actual_profit: p.actualProfit, start_date: p.startDate ?? '',
      end_date: p.endDate ?? '', manager: p.manager ?? '', status: p.status,
    })
    setOpen(true)
  }

  async function save() {
    setBusy(true)
    try {
      const body = { ...form, capital_required: Number(form.capital_required), expected_profit: Number(form.expected_profit) }
      if (editing) await updateProject(editing.id, body)
      else await createProject(body)
      toast(t('common.save') + ' ✓')
      setOpen(false)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  async function remove(p: any) {
    try {
      await deleteProject(p.id)
      toast(t('common.deleted'))
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  return (
    <>
      <PageHeader
        title={t('projects.title')}
        subtitle={t('projects.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => { setEditing(null); setForm(EMPTY); setOpen(true) }}>{t('projects.addProject')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('dashboard.activeProjects')} value={active} />
        <StatCard index={1} tone="secondary" label={t('dashboard.projectCapital')} value={formatMoney(capital, { compact: true })} />
        <StatCard index={2} tone="tertiary" label={t('projects.actualProfit')} value={formatMoney(profit, { compact: true })} />
      </div>

      {error ? (
        <Card className="mt-4"><EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} /></Card>
      ) : loading ? (
        <Card className="mt-4"><EmptyState title={t('common.loading')} /></Card>
      ) : (
        <div className="mt-4 grid gap-4 md:grid-cols-2">
          {projects.map((p, i) => {
            const pct = p.capitalRequired ? (p.capitalRaised / p.capitalRequired) * 100 : 0
            return (
              <motion.div key={p.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
                <Card>
                  <CardBody>
                    <div className="flex items-start justify-between gap-3">
                      <button className="min-w-0 text-left" onClick={() => navigate(`/admin/projects/${p.id}`)}>
                        <h3 className="font-display text-base font-bold text-neutral-900">{p.name}</h3>
                        <p className="mt-0.5 line-clamp-2 text-[13px] text-neutral-500">{p.description}</p>
                      </button>
                      <div className="flex items-center gap-1">
                        <Badge tone={p.status === 'active' ? 'success' : p.status === 'completed' ? 'info' : p.status === 'planned' ? 'warning' : 'neutral'}>
                          {t(`projects.status.${p.status}`)}
                        </Badge>
                        <RowActions onEdit={() => startEdit(p)} onDelete={() => remove(p)} deleteMessage={t('common.confirmDelete')} />
                      </div>
                    </div>

                    <div className="mt-4">
                      <div className="mb-1 flex justify-between text-[12px] text-neutral-500">
                        <span>{t('projects.fundingProgress')}</span>
                        <span>{formatMoney(p.capitalRaised, { compact: true })} / {formatMoney(p.capitalRequired, { compact: true })}</span>
                      </div>
                      <Progress value={pct} tone={pct >= 100 ? 'tertiary' : 'primary'} showLabel />
                    </div>

                    <div className="mt-4 flex items-center justify-between border-t border-neutral-100 pt-3 text-[13px] text-neutral-500">
                      <span className="inline-flex items-center gap-1.5"><Users className="h-4 w-4" /> {p.participantCount}</span>
                      <span>{t(`projects.type.${p.type}`)}</span>
                      <span>{p.endDate ? formatDate(p.endDate, 'short') : '—'}</span>
                    </div>
                  </CardBody>
                </Card>
              </motion.div>
            )
          })}
        </div>
      )}

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={editing ? `${t('common.edit')} — ${editing.name}` : t('projects.addProject')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button loading={busy} onClick={save}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('projects.projectName')}><Input value={form.name} onChange={(e) => setForm((f: any) => ({ ...f, name: e.target.value }))} /></Field>
          <Field label={t('common.description')}><Input value={form.description} onChange={(e) => setForm((f: any) => ({ ...f, description: e.target.value }))} /></Field>
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('projects.capitalRequired')}><Input type="number" value={form.capital_required} onChange={(e) => setForm((f: any) => ({ ...f, capital_required: e.target.value }))} /></Field>
            <Field label={t('projects.expectedProfit')}><Input type="number" value={form.expected_profit} onChange={(e) => setForm((f: any) => ({ ...f, expected_profit: e.target.value }))} /></Field>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('projects.startDate')}><Input type="date" value={form.start_date} onChange={(e) => setForm((f: any) => ({ ...f, start_date: e.target.value }))} /></Field>
            <Field label={t('projects.endDate')}><Input type="date" value={form.end_date} onChange={(e) => setForm((f: any) => ({ ...f, end_date: e.target.value }))} /></Field>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('common.type')}>
              <Select value={form.type} onChange={(e) => setForm((f: any) => ({ ...f, type: e.target.value }))}>
                {(['monthly', 'three_months', 'long_term', 'custom'] as const).map((ty) => <option key={ty} value={ty}>{t(`projects.type.${ty}`)}</option>)}
              </Select>
            </Field>
            {editing && (
              <Field label={t('common.status')}>
                <Select value={form.status} onChange={(e) => setForm((f: any) => ({ ...f, status: e.target.value }))}>
                  {(['planned', 'active', 'completed', 'cancelled'] as const).map((s) => <option key={s} value={s}>{t(`projects.status.${s}`)}</option>)}
                </Select>
              </Field>
            )}
          </div>
          <Field label={t('projects.manager')}><Input value={form.manager} onChange={(e) => setForm((f: any) => ({ ...f, manager: e.target.value }))} /></Field>
        </div>
      </Modal>
    </>
  )
}
