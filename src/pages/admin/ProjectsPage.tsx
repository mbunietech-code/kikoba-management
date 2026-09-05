import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Plus, Users } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, Field, Input, Modal, PageHeader, Progress, Select, StatCard, useToast,
} from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { projects } from '@/mock/data'
import { sum } from '@/mock/selectors'

export default function ProjectsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const [open, setOpen] = useState(false)

  const active = projects.filter((p) => p.status === 'active')
  const capital = sum(projects.map((p) => p.capitalRaised))
  const profit = sum(projects.map((p) => p.actualProfit))

  return (
    <>
      <PageHeader
        title={t('projects.title')}
        subtitle={t('projects.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('projects.addProject')}</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('dashboard.activeProjects')} value={active.length} />
        <StatCard index={1} tone="secondary" label={t('dashboard.projectCapital')} value={formatMoney(capital, { compact: true })} />
        <StatCard index={2} tone="tertiary" label={t('projects.actualProfit')} value={formatMoney(profit, { compact: true })} />
      </div>

      <div className="mt-4 grid gap-4 md:grid-cols-2">
        {projects.map((p, i) => {
          const pct = p.capitalRequired ? (p.capitalRaised / p.capitalRequired) * 100 : 0
          return (
            <motion.div key={p.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
              <Card className="cursor-pointer transition-shadow hover:shadow-[var(--shadow-pop)]" onClick={() => navigate(`/admin/projects/${p.id}`)}>
                <CardBody>
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <h3 className="font-display text-base font-bold text-neutral-900">{p.name}</h3>
                      <p className="mt-0.5 line-clamp-2 text-[13px] text-neutral-500">{p.description}</p>
                    </div>
                    <Badge tone={p.status === 'active' ? 'success' : p.status === 'completed' ? 'info' : p.status === 'planned' ? 'warning' : 'neutral'}>
                      {t(`projects.status.${p.status}`)}
                    </Badge>
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
                    <span>{formatDate(p.endDate, 'short')}</span>
                  </div>
                </CardBody>
              </Card>
            </motion.div>
          )
        })}
      </div>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('projects.addProject')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('common.create') + ' ✓') }}>{t('common.create')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('projects.projectName')}><Input placeholder="e.g. Sunflower Oil Pressing" /></Field>
          <Field label={t('common.description')}><Input placeholder="Short summary" /></Field>
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('projects.capitalRequired')}><Input type="number" /></Field>
            <Field label={t('projects.expectedProfit')}><Input type="number" /></Field>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Field label={t('projects.startDate')}><Input type="date" /></Field>
            <Field label={t('projects.endDate')}><Input type="date" /></Field>
          </div>
          <Field label={t('common.type')}>
            <Select>{(['monthly', 'three_months', 'long_term', 'custom'] as const).map((ty) => <option key={ty} value={ty}>{t(`projects.type.${ty}`)}</option>)}</Select>
          </Field>
        </div>
      </Modal>
    </>
  )
}
