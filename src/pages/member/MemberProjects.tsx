import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import { motion } from 'motion/react'
import { Badge, Card, CardBody, EmptyState, PageHeader, Progress, StatCard } from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { projectInvestments, projects } from '@/mock/data'
import { sum } from '@/mock/selectors'
import { useMemberId } from './useMember'

export default function MemberProjects() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const memberId = useMemberId()

  const myInvs = projectInvestments.filter((p) => p.memberId === memberId)
  const invested = sum(myInvs.map((i) => i.amount))
  const returns = sum(myInvs.map((i) => i.amount + i.profitShare))

  return (
    <>
      <PageHeader title={t('nav.myProjects')} subtitle={t('projects.subtitle')} />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} label={t('projects.myInvestment')} value={formatMoney(invested, { compact: true })} />
        <StatCard index={1} tone="tertiary" label={t('projects.totalReturn')} value={formatMoney(returns, { compact: true })} />
        <StatCard index={2} tone="secondary" label={t('common.total')} value={myInvs.length} />
      </div>

      {myInvs.length === 0 ? (
        <Card className="mt-4"><CardBody><EmptyState title={t('common.noData')} hint={t('projects.subtitle')} /></CardBody></Card>
      ) : (
        <div className="mt-4 grid gap-4 md:grid-cols-2">
          {myInvs.map((inv, i) => {
            const project = projects.find((p) => p.id === inv.projectId)!
            const pct = project.capitalRequired ? (project.capitalRaised / project.capitalRequired) * 100 : 0
            return (
              <motion.div key={inv.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
                <Card className="cursor-pointer hover:shadow-[var(--shadow-pop)]" onClick={() => navigate(`/member/projects/${project.id}`)}>
                  <CardBody>
                    <div className="flex items-start justify-between">
                      <h3 className="font-display text-base font-bold text-neutral-900">{project.name}</h3>
                      <Badge tone={project.status === 'active' ? 'success' : project.status === 'completed' ? 'info' : 'neutral'}>{t(`projects.status.${project.status}`)}</Badge>
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-3 text-[13px]">
                      <div><p className="text-[11px] uppercase text-neutral-400">{t('projects.myInvestment')}</p><p className="font-medium">{formatMoney(inv.amount)}</p></div>
                      <div><p className="text-[11px] uppercase text-neutral-400">{t('projects.profitShare')}</p><p className="font-medium">{formatMoney(inv.profitShare)}</p></div>
                    </div>
                    <div className="mt-3">
                      <Progress value={pct} tone={pct >= 100 ? 'tertiary' : 'primary'} showLabel />
                    </div>
                    <p className="mt-2 text-[12px] text-neutral-400">{formatDate(project.startDate, 'short')} – {formatDate(project.endDate, 'short')}</p>
                  </CardBody>
                </Card>
              </motion.div>
            )
          })}
        </div>
      )}
    </>
  )
}
