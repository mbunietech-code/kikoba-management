import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button, Card, CardBody, Field, Input, PageHeader, Select, Tabs, useToast } from '@/components/ui'

export default function SettingsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [tab, setTab] = useState('organization')

  const save = () => toast(t('settings.saved'))

  return (
    <>
      <PageHeader
        title={t('settings.title')}
        subtitle={t('settings.subtitle')}
        actions={<Button onClick={save}>{t('common.saveChanges')}</Button>}
      />

      <Card>
        <div className="px-3 pt-2">
          <Tabs
            value={tab}
            onChange={setTab}
            items={(['organization', 'financial', 'loans', 'insurance', 'notifications', 'integrations'] as const).map((k) => ({
              key: k,
              label: t(`settings.tabs.${k}`),
            }))}
          />
        </div>

        {tab === 'organization' && (
          <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
            <Field label={t('settings.organizationName')} className="sm:col-span-2"><Input defaultValue="Benja Kikoba" /></Field>
            <Field label={t('settings.registrationNumber')}><Input defaultValue="TZ-SACCO-2021-0473" /></Field>
            <Field label={t('settings.currency')}>
              <Select defaultValue="TZS">
                <option value="TZS">TZS — Tanzanian Shilling</option>
                <option value="KES">KES — Kenyan Shilling</option>
                <option value="USD">USD — US Dollar</option>
              </Select>
            </Field>
            <Field label={t('common.phone')}><Input defaultValue="+255 27 254 0000" /></Field>
            <Field label={t('common.email')}><Input defaultValue="info@benjakikoba.co.tz" /></Field>
          </CardBody>
        )}

        {tab === 'financial' && (
          <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
            <Field label={t('settings.sharePrice')}><Input type="number" defaultValue={10000} /></Field>
            <Field label={t('settings.minShares')}><Input type="number" defaultValue={10} /></Field>
            <Field label={t('settings.minSavings')}><Input type="number" defaultValue={100000} /></Field>
            <Field label={t('settings.reserveRate')} hint="%"><Input type="number" defaultValue={20} /></Field>
          </CardBody>
        )}

        {tab === 'loans' && (
          <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
            <Field label={t('settings.loanInterestRate')} hint="% / year"><Input type="number" defaultValue={10} /></Field>
            <Field label={t('settings.penaltyRate')} hint="%"><Input type="number" defaultValue={5} /></Field>
            <Field label={t('loans.frequency')}>
              <Select>{(['weekly', 'biweekly', 'monthly', 'quarterly'] as const).map((f) => <option key={f} value={f}>{t(`loans.freq.${f}`)}</option>)}</Select>
            </Field>
            <Field label={t('products.requiredGuarantors')}><Input type="number" defaultValue={2} /></Field>
          </CardBody>
        )}

        {tab === 'insurance' && (
          <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
            <Field label={t('settings.insuranceContribution')}><Input type="number" defaultValue={20000} /></Field>
            <Field label={t('insurance.coverageAmount')}><Input type="number" defaultValue={2000000} /></Field>
          </CardBody>
        )}

        {tab === 'notifications' && (
          <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
            <Field label={t('settings.smsGateway')}><Input defaultValue="Beem Africa" /></Field>
            <Field label={t('settings.emailProvider')}><Input defaultValue="SendGrid" /></Field>
          </CardBody>
        )}

        {tab === 'integrations' && (
          <CardBody className="grid max-w-2xl gap-4 sm:grid-cols-2">
            <Field label={t('settings.paymentGateway')}><Input defaultValue="Selcom / M-Pesa" /></Field>
            <Field label="API base URL"><Input defaultValue="https://api.benjakikoba.co.tz/v1" /></Field>
          </CardBody>
        )}
      </Card>
    </>
  )
}
