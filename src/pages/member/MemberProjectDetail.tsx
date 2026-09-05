import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, DescriptionList, Field, Input, Modal, PageHeader,
  Progress, StatCard, useToast,
} from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { formatDate, formatMoney } from '@/lib/format'
import { projectInvestments, projects } from '@/mock/data'
import { useMemberId } from './useMember'

export default function MemberProjectDetail() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { id = '' } = useParams()
  const memberId = useMemberId()
  const [open, setOpen] = useState(false)

  const project = projects.find((p) => p.id === id)
  if (!project) return <NotFoundInline />

  const myInv = projectInvestments.find((p) => p.projectId === id && p.memberId === memberId)
  const pct = project.capitalRequired ? (project.capitalRaised / project.capitalRequired) * 100 : 0
  const canInvest = project.status === 'planned' || (project.status === 'active' && pct < 100)

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/member/projects')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('nav.myProjects')}
          </button>
        }
        title={project.name}
        subtitle={t(`projects.type.${project.type}`)}
        actions={canInvest ? <Button onClick={() => setOpen(true)}>{t('projects.invest')}</Button> : null}
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_340px]">
        <Card>
          <CardBody>
            <p className="text-[14px] text-neutral-600">{project.description}</p>
            <div className="mt-4">
              <div className="mb-1 flex justify-between text-[12px] text-neutral-500">
                <span>{t('projects.fundingProgress')}</span><span>{Math.round(pct)}%</span>
              </div>
              <Progress value={pct} tone={pct >= 100 ? 'tertiary' : 'primary'} />
              <p className="mt-2 text-[13px] text-neutral-600">{formatMoney(project.capitalRaised)} / {formatMoney(project.capitalRequired)}</p>
            </div>
            <div className="mt-5">
              <DescriptionList
                items={[
                  { label: t('projects.manager'), value: project.manager },
                  { label: t('projects.participants'), value: project.participantCount },
                  { label: t('projects.startDate'), value: formatDate(project.startDate) },
                  { label: t('projects.endDate'), value: formatDate(project.endDate) },
                  { label: t('projects.expectedProfit'), value: formatMoney(project.expectedProfit) },
                  { label: t('common.status'), value: <Badge tone={project.status === 'active' ? 'success' : 'neutral'}>{t(`projects.status.${project.status}`)}</Badge> },
                ]}
              />
            </div>
          </CardBody>
        </Card>

        <div className="flex flex-col gap-4">
          <Card>
            <CardHeader title={t('projects.myInvestment')} className="!py-3" />
            <CardBody>
              {myInv ? (
                <>
                  <p className="font-display text-2xl font-bold text-neutral-900">{formatMoney(myInv.amount)}</p>
                  <div className="mt-3 space-y-1.5 text-[13px]">
                    <div className="flex justify-between"><span className="text-neutral-500">{t('projects.profitShare')}</span><span className="font-medium">{formatMoney(myInv.profitShare)}</span></div>
                    <div className="flex justify-between border-t border-neutral-100 pt-1.5"><span className="font-semibold">{t('projects.totalReturn')}</span><span className="font-bold">{formatMoney(myInv.amount + myInv.profitShare)}</span></div>
                  </div>
                </>
              ) : (
                <p className="text-sm text-neutral-400">{t('common.noData')}</p>
              )}
            </CardBody>
          </Card>
          <StatCard index={0} tone="tertiary" label={t('projects.actualProfit')} value={formatMoney(project.actualProfit, { compact: true })} />
        </div>
      </div>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('projects.invest')}
        description={project.name}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('projects.invest') + ' ✓') }}>{t('common.submit')}</Button>
          </>
        }
      >
        <Field label={t('common.amount')}><Input type="number" placeholder="0" /></Field>
      </Modal>
    </>
  )
}
