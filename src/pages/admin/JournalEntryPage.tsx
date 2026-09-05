import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import { Badge, Card, CardBody, CardHeader, DescriptionList, PageHeader } from '@/components/ui'
import { NotFoundInline } from '@/components/NotFoundInline'
import { formatDate, formatMoney } from '@/lib/format'
import { journalEntries } from '@/mock/data'
import { sum } from '@/mock/selectors'

export default function JournalEntryPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { id = '' } = useParams()
  const entry = journalEntries.find((j) => j.id === id)
  if (!entry) return <NotFoundInline />

  const totalDebit = sum(entry.lines.map((l) => l.debit))
  const totalCredit = sum(entry.lines.map((l) => l.credit))
  const balanced = totalDebit === totalCredit

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/admin/accounting')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('accounting.title')}
          </button>
        }
        title={entry.reference}
        subtitle={entry.description}
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
        <Card>
          <CardHeader
            title={t('accounting.journalEntries')}
            action={<Badge tone={balanced ? 'success' : 'danger'} dot>{balanced ? t('accounting.balanced') : t('accounting.unbalanced')}</Badge>}
          />
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-neutral-200 text-left text-[12px] uppercase tracking-wide text-neutral-500">
                  <th className="px-5 py-3">{t('accounting.accountName')}</th>
                  <th className="px-5 py-3 text-right">{t('accounting.debit')}</th>
                  <th className="px-5 py-3 text-right">{t('accounting.credit')}</th>
                </tr>
              </thead>
              <tbody>
                {entry.lines.map((l, i) => (
                  <tr key={i} className="border-b border-neutral-100">
                    <td className="px-5 py-3">
                      <span className="font-mono text-[12px] text-neutral-500">{l.accountCode}</span>{' '}
                      <span className="font-medium text-neutral-800">{l.accountName}</span>
                    </td>
                    <td className="px-5 py-3 text-right tabular-nums">{l.debit ? formatMoney(l.debit) : '—'}</td>
                    <td className="px-5 py-3 text-right tabular-nums">{l.credit ? formatMoney(l.credit) : '—'}</td>
                  </tr>
                ))}
                <tr className="font-semibold">
                  <td className="px-5 py-3">{t('common.total')}</td>
                  <td className="px-5 py-3 text-right tabular-nums">{formatMoney(totalDebit)}</td>
                  <td className="px-5 py-3 text-right tabular-nums">{formatMoney(totalCredit)}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>

        <Card>
          <CardHeader title={t('common.details')} className="!py-3" />
          <CardBody>
            <DescriptionList
              columns={1}
              items={[
                { label: t('common.reference'), value: entry.reference },
                { label: t('accounting.entryDate'), value: formatDate(entry.entryDate) },
                { label: t('accounting.postedBy'), value: entry.postedBy },
                { label: t('transactions.txnRef'), value: <span className="font-mono text-[12px]">{entry.transactionRef}</span> },
              ]}
            />
          </CardBody>
        </Card>
      </div>
    </>
  )
}
