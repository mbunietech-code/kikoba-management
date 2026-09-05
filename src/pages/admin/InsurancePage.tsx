import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { FilePlus2, Plus } from 'lucide-react'
import {
  Badge, Button, Card, DataTable, Field, Input, Modal, PageHeader, Select, SkeletonTable, StatCard,
  StatusBadge, Tabs, useToast,
} from '@/components/ui'
import type { Column } from '@/components/ui'
import { ListToolbar } from '@/components/ListToolbar'
import { MemberCell } from '@/components/MemberCell'
import { useMockQuery } from '@/lib/useMockQuery'
import { formatDate, formatMoney } from '@/lib/format'
import { activeMembers, insuranceAccounts, insuranceClaims } from '@/mock/data'
import { sum } from '@/mock/selectors'
import type { InsuranceAccount, InsuranceClaim } from '@/types'

export default function InsurancePage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { data, loading } = useMockQuery(() => ({ accounts: insuranceAccounts, claims: insuranceClaims }), [])
  const [tab, setTab] = useState('accounts')
  const [search, setSearch] = useState('')
  const [modal, setModal] = useState<null | 'contribution' | 'claim'>(null)

  const accounts = useMemo(
    () => (data?.accounts ?? []).filter((a) => (a.planName + ' ' + a.status).toLowerCase().includes(search.toLowerCase())),
    [data, search],
  )
  const claims = data?.claims ?? []

  const contributions = sum(insuranceAccounts.map((a) => a.totalContributed))
  const claimsPaid = sum(insuranceClaims.filter((c) => c.status === 'paid').map((c) => c.amountApproved))
  const covered = insuranceAccounts.filter((a) => a.status === 'active').length

  const accountCols: Column<InsuranceAccount>[] = [
    { key: 'member', header: t('common.member'), render: (a) => <MemberCell memberId={a.memberId} /> },
    { key: 'plan', header: t('insurance.planName'), render: (a) => a.planName },
    { key: 'monthly', header: t('insurance.monthlyContribution'), align: 'right', render: (a) => formatMoney(a.monthlyContribution) },
    { key: 'coverage', header: t('insurance.coverageAmount'), align: 'right', render: (a) => formatMoney(a.coverageAmount) },
    { key: 'contributed', header: t('insurance.totalContributions'), align: 'right', sortValue: (a) => a.totalContributed, render: (a) => <span className="font-medium">{formatMoney(a.totalContributed)}</span> },
    { key: 'expiry', header: t('insurance.endDate'), render: (a) => formatDate(a.endDate) },
    { key: 'status', header: t('common.status'), render: (a) => <StatusBadge status={a.status} label={t(`insurance.status.${a.status}`)} /> },
  ]
  const claimCols: Column<InsuranceClaim>[] = [
    { key: 'no', header: t('insurance.claimNumber'), render: (c) => <span className="font-medium text-primary-700">{c.claimNumber}</span> },
    { key: 'member', header: t('common.member'), render: (c) => <MemberCell memberId={c.memberId} /> },
    { key: 'type', header: t('insurance.claimType'), render: (c) => <Badge tone="neutral">{c.claimType}</Badge> },
    { key: 'requested', header: t('insurance.amountRequested'), align: 'right', render: (c) => formatMoney(c.amountRequested) },
    { key: 'approved', header: t('insurance.amountApproved'), align: 'right', render: (c) => (c.amountApproved ? formatMoney(c.amountApproved) : '—') },
    { key: 'submitted', header: t('insurance.submittedAt'), sortValue: (c) => c.submittedAt, render: (c) => formatDate(c.submittedAt) },
    { key: 'status', header: t('common.status'), render: (c) => <StatusBadge status={c.status} label={t(`insurance.claimStatus.${c.status}`)} /> },
    {
      key: 'act', header: '', align: 'right',
      render: (c) => (['submitted', 'under_review'].includes(c.status) ? <Button size="sm" onClick={() => toast(t('common.approve') + ' ✓')}>{t('common.approve')}</Button> : null),
    },
  ]

  return (
    <>
      <PageHeader
        title={t('insurance.title')}
        subtitle={t('insurance.subtitle')}
        actions={
          <>
            <Button variant="outlined" leftIcon={<FilePlus2 className="h-4 w-4" />} onClick={() => setModal('claim')}>{t('insurance.fileClaim')}</Button>
            <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setModal('contribution')}>{t('insurance.recordContribution')}</Button>
          </>
        }
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard index={0} tone="tertiary" label={t('insurance.totalContributions')} value={formatMoney(contributions, { compact: true })} />
        <StatCard index={1} tone="neutral" label={t('insurance.totalClaims')} value={formatMoney(claimsPaid, { compact: true })} />
        <StatCard index={2} tone="secondary" label={t('insurance.activeCoverage')} value={covered} />
      </div>

      <Card className="mt-4">
        <div className="px-3 pt-2">
          <Tabs
            value={tab}
            onChange={setTab}
            items={[
              { key: 'accounts', label: t('savings.accounts'), count: insuranceAccounts.length },
              { key: 'claims', label: t('nav.claims'), count: insuranceClaims.length },
            ]}
          />
        </div>
        <div className="p-3"><ListToolbar search={search} onSearch={setSearch} /></div>
        {loading ? (
          <SkeletonTable />
        ) : tab === 'accounts' ? (
          <DataTable columns={accountCols} rows={accounts} rowKey={(a) => a.id} />
        ) : (
          <DataTable columns={claimCols} rows={claims} rowKey={(c) => c.id} />
        )}
      </Card>

      <Modal
        open={modal !== null}
        onClose={() => setModal(null)}
        title={modal === 'claim' ? t('insurance.fileClaim') : t('insurance.recordContribution')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setModal(null)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setModal(null); toast(t('common.submit') + ' ✓') }}>{t('common.submit')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.member')}>
            <Select>{activeMembers.map((m) => <option key={m.id} value={m.id}>{m.fullName}</option>)}</Select>
          </Field>
          {modal === 'claim' ? (
            <>
              <Field label={t('insurance.claimType')}>
                <Select><option>Medical</option><option>Funeral</option><option>Property loss</option><option>Disability</option></Select>
              </Field>
              <Field label={t('insurance.amountRequested')}><Input type="number" /></Field>
              <Field label={t('common.description')}><Input placeholder="Brief description" /></Field>
            </>
          ) : (
            <>
              <Field label={t('common.amount')}><Input type="number" defaultValue={20000} /></Field>
              <Field label={t('reports.period')}><Input type="month" /></Field>
            </>
          )}
        </div>
      </Modal>
    </>
  )
}
