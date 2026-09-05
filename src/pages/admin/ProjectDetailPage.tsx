import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import {
  Badge, Card, CardBody, CardHeader, DataTable, DescriptionList, PageHeader, Progress, StatCard,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { MemberCell } from '@/components/MemberCell'
import { formatDate, formatMoney } from '@/lib/format'
import { projectInvestments, projects } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { ProjectInvestment } from '@/types'

export default function ProjectDetailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { id = '' } = useParams()
  const project = projects.find((p) => p.id === id)
  if (!project) return <NotFoundInline />

  const invs = projectInvestments.filter((p) => p.projectId === id)
  const totalInvested = sum(invs.map((i) => i.amount))
  const pct = project.capitalRequired ? (project.capitalRaised / project.capitalRequired) * 100 : 0

  const cols: Column<ProjectInvestment>[] = [
    { key: 'member', header: t('common.member'), render: (i) => <MemberCell memberId={i.memberId} /> },
    { key: 'amount', header: t('projects.myInvestment'), align: 'right', sortValue: (i) => i.amount, render: (i) => formatMoney(i.amount) },
    { key: 'share', header: t('projects.profitShare'), align: 'right', render: (i) => formatMoney(i.profitShare) },
    { key: 'return', header: t('projects.totalReturn'), align: 'right', render: (i) => <span className="font-medium">{formatMoney(i.amount + i.profitShare)}</span> },
    { key: 'date', header: t('common.date'), render: (i) => formatDate(i.investedAt) },
    { key: 'status', header: t('common.status'), render: (i) => <Badge tone={i.status === 'completed' ? 'info' : 'success'}>{i.status}</Badge> },
  ]

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/admin/projects')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('projects.title')}
          </button>
        }
        title={project.name}
        subtitle={t(`projects.type.${project.type}`)}
      />

      <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div className="flex flex-col gap-4">
          <Card>
            <CardBody>
              <div className="mb-1 flex justify-between text-[12px] text-neutral-500">
                <span>{t('projects.fundingProgress')}</span><span>{Math.round(pct)}%</span>
              </div>
              <Progress value={pct} tone={pct >= 100 ? 'tertiary' : 'primary'} />
              <p className="mt-2 text-[13px] text-neutral-600">{formatMoney(project.capitalRaised)} / {formatMoney(project.capitalRequired)}</p>
            </CardBody>
          </Card>
          <div className="grid grid-cols-2 gap-3">
            <StatCard index={0} tone="secondary" label={t('projects.expectedProfit')} value={formatMoney(project.expectedProfit, { compact: true })} />
            <StatCard index={1} tone="tertiary" label={t('projects.actualProfit')} value={formatMoney(project.actualProfit, { compact: true })} />
          </div>
          <Card>
            <CardHeader title={t('common.details')} className="!py-3" />
            <CardBody>
              <DescriptionList
                columns={1}
                items={[
                  { label: t('projects.manager'), value: project.manager },
                  { label: t('projects.startDate'), value: formatDate(project.startDate) },
                  { label: t('projects.endDate'), value: formatDate(project.endDate) },
                  { label: t('projects.participants'), value: project.participantCount },
                  { label: t('common.status'), value: <Badge tone={project.status === 'active' ? 'success' : 'neutral'}>{t(`projects.status.${project.status}`)}</Badge> },
                  { label: t('projects.capitalRaised'), value: formatMoney(totalInvested) },
                ]}
              />
            </CardBody>
          </Card>
        </div>

        <Card className="min-w-0">
          <CardHeader title={t('projects.participants')} subtitle={`${invs.length} ${t('projects.participants').toLowerCase()}`} />
          <DataTable columns={cols} rows={invs} rowKey={(i) => i.id} empty={{ title: t('common.noData') }} />
        </Card>
      </div>
    </>
  )
}
