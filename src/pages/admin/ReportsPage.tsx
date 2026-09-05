import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import {
  BarChart3, Download, FileText, HandCoins, PiggyBank, PieChart, ShieldCheck, TrendingUp, Users,
} from 'lucide-react'
import { Badge, Button, Card, CardBody, PageHeader, Select, useToast } from '@/components/ui'
import { formatDate } from '@/lib/format'

const REPORTS = [
  { key: 'membership', icon: Users, tone: 'primary' },
  { key: 'shares', icon: PieChart, tone: 'tertiary' },
  { key: 'savings', icon: PiggyBank, tone: 'secondary' },
  { key: 'loans', icon: HandCoins, tone: 'primary' },
  { key: 'profit', icon: TrendingUp, tone: 'tertiary' },
  { key: 'projects', icon: BarChart3, tone: 'secondary' },
  { key: 'insurance', icon: ShieldCheck, tone: 'primary' },
  { key: 'financial', icon: FileText, tone: 'neutral' },
] as const

const toneClass: Record<string, string> = {
  primary: 'bg-primary-50 text-primary-700',
  secondary: 'bg-secondary-50 text-secondary-700',
  tertiary: 'bg-tertiary-50 text-tertiary-700',
  neutral: 'bg-neutral-100 text-neutral-600',
}

export default function ReportsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [period, setPeriod] = useState('this_year')
  const [format, setFormat] = useState('pdf')

  const generated = [
    { name: t('reports.financial'), date: '2026-08-31', format: 'PDF' },
    { name: t('reports.loans'), date: '2026-08-15', format: 'XLSX' },
    { name: t('reports.membership'), date: '2026-07-31', format: 'CSV' },
  ]

  return (
    <>
      <PageHeader
        title={t('reports.title')}
        subtitle={t('reports.subtitle')}
        actions={
          <>
            <Select value={period} onChange={(e) => setPeriod(e.target.value)} className="w-auto">
              <option value="this_month">{t('common.thisMonth')}</option>
              <option value="this_year">{t('common.thisYear')}</option>
              <option value="last_year">2025</option>
            </Select>
            <Select value={format} onChange={(e) => setFormat(e.target.value)} className="w-auto">
              <option value="pdf">PDF</option>
              <option value="xlsx">Excel</option>
              <option value="csv">CSV</option>
            </Select>
          </>
        }
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {REPORTS.map((r, i) => (
          <motion.div key={r.key} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.04 }}>
            <Card className="flex h-full flex-col">
              <CardBody className="flex flex-1 flex-col">
                <span className={`flex h-10 w-10 items-center justify-center rounded-xl ${toneClass[r.tone]}`}>
                  <r.icon className="h-5 w-5" />
                </span>
                <h3 className="mt-3 font-display text-[15px] font-bold text-neutral-900">{t(`reports.${r.key}`)}</h3>
                <p className="mt-1 flex-1 text-[13px] text-neutral-500">{t('reports.asOf')} {formatDate(new Date())}</p>
                <Button
                  variant="outlined"
                  size="sm"
                  className="mt-4 self-start"
                  leftIcon={<Download className="h-4 w-4" />}
                  onClick={() => toast(`${t(`reports.${r.key}`)} — ${t('reports.generate')} ✓`)}
                >
                  {t('reports.generate')}
                </Button>
              </CardBody>
            </Card>
          </motion.div>
        ))}
      </div>

      <Card className="mt-4">
        <CardBody className="!p-0">
          <div className="border-b border-neutral-100 px-5 py-4">
            <h3 className="text-[15px] font-semibold text-neutral-900">{t('reports.generatedReports')}</h3>
          </div>
          <ul className="divide-y divide-neutral-100">
            {generated.map((g, i) => (
              <li key={i} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <FileText className="h-4 w-4 text-neutral-400" />
                  <div>
                    <p className="text-[13.5px] font-medium text-neutral-800">{g.name}</p>
                    <p className="text-[12px] text-neutral-400">{formatDate(g.date)}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Badge tone="neutral">{g.format}</Badge>
                  <Button variant="ghost" size="sm" leftIcon={<Download className="h-4 w-4" />}>{t('common.download')}</Button>
                </div>
              </li>
            ))}
          </ul>
        </CardBody>
      </Card>
    </>
  )
}
