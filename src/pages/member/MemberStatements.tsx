import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Download, FileText } from 'lucide-react'
import { Badge, Button, Card, CardBody, CardHeader, DescriptionList, Field, PageHeader, Select, useToast } from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { memberPosition } from '@/mock/selectors'
import { useCurrentMember, useMemberId } from './useMember'

export default function MemberStatements() {
  const { t } = useTranslation()
  const toast = useToast()
  const memberId = useMemberId()
  const member = useCurrentMember()
  const pos = memberPosition(memberId)
  const [period, setPeriod] = useState('this_year')
  const [format, setFormat] = useState('pdf')

  const history = [
    { name: `${t('statements.title')} — Q2 2026`, date: '2026-06-30', format: 'PDF' },
    { name: `${t('statements.title')} — Q1 2026`, date: '2026-03-31', format: 'PDF' },
    { name: `${t('statements.title')} — 2025`, date: '2025-12-31', format: 'PDF' },
  ]

  return (
    <>
      <PageHeader title={t('nav.statements')} subtitle={t('statements.subtitle')} />

      <div className="grid gap-4 lg:grid-cols-[1fr_340px]">
        <Card>
          <CardHeader title={t('statements.generate')} />
          <CardBody className="grid gap-4">
            <Field label={t('statements.period')}>
              <Select value={period} onChange={(e) => setPeriod(e.target.value)}>
                <option value="this_month">{t('common.thisMonth')}</option>
                <option value="this_year">{t('common.thisYear')}</option>
                <option value="2025">2025</option>
              </Select>
            </Field>
            <Field label={t('reports.format')}>
              <Select value={format} onChange={(e) => setFormat(e.target.value)}>
                <option value="pdf">PDF</option>
                <option value="csv">CSV</option>
              </Select>
            </Field>
            <Button leftIcon={<Download className="h-4 w-4" />} onClick={() => toast(t('statements.generate') + ' ✓')}>
              {t('statements.generate')}
            </Button>

            <div className="mt-2 rounded-xl bg-neutral-50 p-4">
              <p className="text-[13px] font-semibold text-neutral-700">{member.fullName} · {member.memberNumber}</p>
              <div className="mt-3">
                <DescriptionList
                  columns={2}
                  items={[
                    { label: t('member.shareValue'), value: formatMoney(pos.shareValue) },
                    { label: t('member.savingsBalance'), value: formatMoney(pos.savingsBalance) },
                    { label: t('member.outstanding'), value: formatMoney(pos.loanOutstanding) },
                    { label: t('member.projectInvestment'), value: formatMoney(pos.projectInvestment) },
                    { label: t('member.myProfit'), value: formatMoney(pos.profit) },
                    { label: t('statements.closing'), value: <span className="font-semibold">{formatMoney(pos.shareValue + pos.savingsBalance + pos.projectInvestment)}</span> },
                  ]}
                />
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardHeader title={t('reports.generatedReports')} className="!py-3" />
          <CardBody className="!p-0">
            <ul className="divide-y divide-neutral-100">
              {history.map((h, i) => (
                <li key={i} className="flex items-center justify-between px-5 py-3">
                  <div className="flex items-center gap-3">
                    <FileText className="h-4 w-4 text-neutral-400" />
                    <div>
                      <p className="text-[13px] font-medium text-neutral-800">{h.name}</p>
                      <p className="text-[12px] text-neutral-400">{formatDate(h.date)}</p>
                    </div>
                  </div>
                  <Badge tone="neutral">{h.format}</Badge>
                </li>
              ))}
            </ul>
          </CardBody>
        </Card>
      </div>
    </>
  )
}
