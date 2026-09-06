import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button, Card, CardBody, Field, Input, PageHeader, Select, Tabs, useToast, EmptyState } from '@/components/ui'
import { useApiQuery } from '@/lib/useApi'
import { getSettings, updateOrganization, updateSettings } from '@/api'
import { useOrg } from '@/app/org'

export default function SettingsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { org, refresh: refreshOrg } = useOrg()
  const [tab, setTab] = useState('organization')
  const [busy, setBusy] = useState(false)

  const { data: settings, loading, error, refetch } = useApiQuery(() => getSettings(), [])

  const [orgForm, setOrgForm] = useState({
    name: org.name, registration_number: '', phone: '', email: '', address: '', currency: org.currency,
  })
  const [cfg, setCfg] = useState<Record<string, string>>({})

  useEffect(() => {
    setOrgForm((f) => ({
      ...f,
      name: org.name,
      currency: org.currency,
      registration_number: org.registrationNumber ?? '',
      phone: org.phone ?? '',
      email: org.email ?? '',
      address: org.address ?? '',
    }))
  }, [org])

  useEffect(() => {
    if (settings) setCfg(settings)
  }, [settings])

  async function saveOrg() {
    setBusy(true)
    try {
      await updateOrganization(orgForm)
      await refreshOrg()
      toast(t('settings.saved'))
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  async function saveConfig(keys: string[]) {
    setBusy(true)
    try {
      await updateSettings(Object.fromEntries(keys.map((k) => [k, cfg[k] ?? ''])))
      refetch()
      toast(t('settings.saved'))
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  const field = (key: string, label: string, hint?: string, type = 'text') => (
    <Field label={label} hint={hint} key={key}>
      <Input type={type} value={cfg[key] ?? ''} onChange={(e) => setCfg((c) => ({ ...c, [key]: e.target.value }))} />
    </Field>
  )

  return (
    <>
      <PageHeader title={t('settings.title')} subtitle={t('settings.subtitle')} />

      {error ? (
        <Card><EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} /></Card>
      ) : (
        <Card>
          <div className="px-3 pt-2">
            <Tabs
              value={tab}
              onChange={setTab}
              items={(['organization', 'financial', 'loans', 'insurance', 'notifications'] as const).map((k) => ({
                key: k, label: t(`settings.tabs.${k}`),
              }))}
            />
          </div>

          {tab === 'organization' && (
            <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
              <Field label={t('settings.organizationName')} className="sm:col-span-2">
                <Input value={orgForm.name} onChange={(e) => setOrgForm((f) => ({ ...f, name: e.target.value }))} />
              </Field>
              <Field label={t('settings.registrationNumber')}>
                <Input value={orgForm.registration_number} onChange={(e) => setOrgForm((f) => ({ ...f, registration_number: e.target.value }))} />
              </Field>
              <Field label={t('settings.currency')}>
                <Select value={orgForm.currency} onChange={(e) => setOrgForm((f) => ({ ...f, currency: e.target.value }))}>
                  <option value="TZS">TZS — Tanzanian Shilling</option>
                  <option value="KES">KES — Kenyan Shilling</option>
                  <option value="UGX">UGX — Ugandan Shilling</option>
                  <option value="USD">USD — US Dollar</option>
                </Select>
              </Field>
              <Field label={t('common.phone')}>
                <Input value={orgForm.phone} onChange={(e) => setOrgForm((f) => ({ ...f, phone: e.target.value }))} />
              </Field>
              <Field label={t('common.email')}>
                <Input value={orgForm.email} onChange={(e) => setOrgForm((f) => ({ ...f, email: e.target.value }))} />
              </Field>
              <Field label={t('members.address')} className="sm:col-span-2">
                <Input value={orgForm.address} onChange={(e) => setOrgForm((f) => ({ ...f, address: e.target.value }))} />
              </Field>
              <div className="sm:col-span-2">
                <Button loading={busy} onClick={saveOrg}>{t('common.saveChanges')}</Button>
              </div>
            </CardBody>
          )}

          {tab === 'financial' && (
            <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
              {loading ? <p className="text-neutral-400">{t('common.loading')}</p> : (
                <>
                  {field('share_price', t('settings.sharePrice'), undefined, 'number')}
                  {field('minimum_shares', t('settings.minShares'), undefined, 'number')}
                  {field('minimum_savings', t('settings.minSavings'), undefined, 'number')}
                  {field('reserve_rate', t('settings.reserveRate'), '%', 'number')}
                  <div className="sm:col-span-2"><Button loading={busy} onClick={() => saveConfig(['share_price', 'minimum_shares', 'minimum_savings', 'reserve_rate'])}>{t('common.saveChanges')}</Button></div>
                </>
              )}
            </CardBody>
          )}

          {tab === 'loans' && (
            <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
              {field('loan_interest_rate', t('settings.loanInterestRate'), '% / year', 'number')}
              {field('penalty_rate', t('settings.penaltyRate'), '%', 'number')}
              <div className="sm:col-span-2"><Button loading={busy} onClick={() => saveConfig(['loan_interest_rate', 'penalty_rate'])}>{t('common.saveChanges')}</Button></div>
            </CardBody>
          )}

          {tab === 'insurance' && (
            <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
              {field('insurance_contribution', t('settings.insuranceContribution'), undefined, 'number')}
              <div className="sm:col-span-2"><Button loading={busy} onClick={() => saveConfig(['insurance_contribution'])}>{t('common.saveChanges')}</Button></div>
            </CardBody>
          )}

          {tab === 'notifications' && (
            <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
              {field('sms_gateway', t('settings.smsGateway'))}
              {field('email_provider', t('settings.emailProvider'))}
              <div className="sm:col-span-2"><Button loading={busy} onClick={() => saveConfig(['sms_gateway', 'email_provider'])}>{t('common.saveChanges')}</Button></div>
            </CardBody>
          )}
        </Card>
      )}
    </>
  )
}
