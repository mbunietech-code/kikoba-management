import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { FilePlus2 } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, DataTable, DescriptionList, Field, Input, Modal,
  PageHeader, Select, StatCard, StatusBadge, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { EmptyState } from '@/components/ui'
import { formatDate, formatMoney } from '@/lib/format'
import { insuranceAccounts, insuranceClaims, insuranceContributions } from '@/mock/data'
import { useMemberId } from './useMember'
import type { InsuranceClaim, InsuranceContribution } from '@/types'

export default function MemberInsurance() {
  const { t } = useTranslation()
  const toast = useToast()
  const memberId = useMemberId()
  const [open, setOpen] = useState(false)

  const account = insuranceAccounts.find((a) => a.memberId === memberId)
  const contributions = insuranceContributions
    .filter((c) => c.memberId === memberId)
    .sort((a, b) => +new Date(b.date) - +new Date(a.date))
  const claims = insuranceClaims.filter((c) => c.memberId === memberId)

  const contribCols: Column<InsuranceContribution>[] = [
    { key: 'period', header: t('reports.period'), render: (c) => c.period },
    { key: 'amount', header: t('common.amount'), align: 'right', render: (c) => formatMoney(c.amount) },
    { key: 'ref', header: t('common.reference'), render: (c) => <span className="font-mono text-[12px] text-neutral-500">{c.reference}</span> },
    { key: 'date', header: t('common.date'), sortValue: (c) => c.date, render: (c) => formatDate(c.date) },
  ]
  const claimCols: Column<InsuranceClaim>[] = [
    { key: 'no', header: t('insurance.claimNumber'), render: (c) => <span className="font-medium text-primary-700">{c.claimNumber}</span> },
    { key: 'type', header: t('insurance.claimType'), render: (c) => <Badge tone="neutral">{c.claimType}</Badge> },
    { key: 'requested', header: t('insurance.amountRequested'), align: 'right', render: (c) => formatMoney(c.amountRequested) },
    { key: 'approved', header: t('insurance.amountApproved'), align: 'right', render: (c) => (c.amountApproved ? formatMoney(c.amountApproved) : '—') },
    { key: 'status', header: t('common.status'), render: (c) => <StatusBadge status={c.status} label={t(`insurance.claimStatus.${c.status}`)} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('nav.myInsurance')}
        subtitle={t('insurance.subtitle')}
        actions={<Button leftIcon={<FilePlus2 className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('insurance.fileClaim')}</Button>}
      />

      {!account ? (
        <Card><CardBody><EmptyState title={t('common.noData')} hint={t('insurance.subtitle')} /></CardBody></Card>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-3">
            <StatCard index={0} label={t('insurance.coverageAmount')} value={formatMoney(account.coverageAmount, { compact: true })} />
            <StatCard index={1} tone="tertiary" label={t('insurance.totalContributions')} value={formatMoney(account.totalContributed, { compact: true })} />
            <StatCard index={2} tone="secondary" label={t('common.status')} value={t(`insurance.status.${account.status}`)} />
          </div>

          <Card className="mt-4">
            <CardHeader title={t('insurance.planName')} subtitle={account.planName} />
            <CardBody>
              <DescriptionList
                items={[
                  { label: t('insurance.monthlyContribution'), value: formatMoney(account.monthlyContribution) },
                  { label: t('insurance.coverageAmount'), value: formatMoney(account.coverageAmount) },
                  { label: t('insurance.startDate'), value: formatDate(account.startDate) },
                  { label: t('insurance.endDate'), value: formatDate(account.endDate) },
                ]}
              />
            </CardBody>
          </Card>

          <Card className="mt-4">
            <CardHeader title={t('insurance.totalContributions')} />
            <DataTable columns={contribCols} rows={contributions} rowKey={(c) => c.id} pageSize={8} empty={{ title: t('common.noData') }} />
          </Card>

          <Card className="mt-4">
            <CardHeader title={t('nav.claims')} />
            <DataTable columns={claimCols} rows={claims} rowKey={(c) => c.id} empty={{ title: t('common.noData') }} />
          </Card>
        </>
      )}

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('insurance.fileClaim')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('common.submit') + ' ✓') }}>{t('common.submit')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('insurance.claimType')}>
            <Select><option>Medical</option><option>Funeral</option><option>Property loss</option><option>Disability</option></Select>
          </Field>
          <Field label={t('insurance.amountRequested')}><Input type="number" /></Field>
          <Field label={t('common.description')}><Input placeholder="Brief description" /></Field>
        </div>
      </Modal>
    </>
  )
}
