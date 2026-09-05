import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import { Button, Card, CardBody, CardHeader, Field, Input, PageHeader, Select, useToast } from '@/components/ui'
import { memberById } from '@/mock/data'

export default function MemberFormPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { id } = useParams()
  const existing = id ? memberById(id) : undefined
  const editing = !!existing

  function submit(e: React.FormEvent) {
    e.preventDefault()
    toast(editing ? t('common.saveChanges') + ' ✓' : t('members.addMember') + ' ✓')
    navigate(editing ? `/admin/members/${id}` : '/admin/members')
  }

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate(-1)} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('members.title')}
          </button>
        }
        title={editing ? `${t('common.edit')} — ${existing?.fullName}` : t('members.addMember')}
        subtitle={t('members.subtitle')}
      />

      <form onSubmit={submit} className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader title={t('members.tabs.profile')} />
          <CardBody className="grid gap-4 sm:grid-cols-2">
            <Field label={t('members.fullName')} className="sm:col-span-2">
              <Input defaultValue={existing?.fullName} required placeholder="Jane Doe" />
            </Field>
            <Field label={t('common.phone')}>
              <Input defaultValue={existing?.phone} required placeholder="+255 7XX XXX XXX" />
            </Field>
            <Field label={t('common.email')}>
              <Input type="email" defaultValue={existing?.email} placeholder="jane@example.co.tz" />
            </Field>
            <Field label={t('members.gender')}>
              <Select defaultValue={existing?.gender ?? 'female'}>
                <option value="female">{t('members.female')}</option>
                <option value="male">{t('members.male')}</option>
                <option value="other">{t('members.other')}</option>
              </Select>
            </Field>
            <Field label={t('members.dob')}>
              <Input type="date" defaultValue={existing?.dateOfBirth} />
            </Field>
            <Field label={t('members.address')} className="sm:col-span-2">
              <Input defaultValue={existing?.address} placeholder="Street, City" />
            </Field>
          </CardBody>
        </Card>

        <div className="flex flex-col gap-4">
          <Card>
            <CardHeader title={t('members.nextOfKin')} />
            <CardBody className="grid gap-4">
              <Field label={t('common.name')}>
                <Input defaultValue={existing?.nextOfKin} />
              </Field>
              <Field label={t('members.nextOfKinPhone')}>
                <Input defaultValue={existing?.nextOfKinPhone} />
              </Field>
            </CardBody>
          </Card>
          <Card>
            <CardHeader title={t('common.status')} />
            <CardBody className="grid gap-4">
              <Field label={t('common.status')}>
                <Select defaultValue={existing?.status ?? 'pending'}>
                  {(['pending', 'active', 'suspended', 'inactive'] as const).map((s) => (
                    <option key={s} value={s}>{t(`members.status.${s}`)}</option>
                  ))}
                </Select>
              </Field>
              <Field label={t('members.registrationDate')}>
                <Input type="date" defaultValue={existing?.registrationDate ?? new Date().toISOString().slice(0, 10)} />
              </Field>
            </CardBody>
          </Card>

          <div className="flex gap-2">
            <Button type="button" variant="outlined" className="flex-1" onClick={() => navigate(-1)}>
              {t('common.cancel')}
            </Button>
            <Button type="submit" className="flex-1">
              {editing ? t('common.saveChanges') : t('common.create')}
            </Button>
          </div>
        </div>
      </form>
    </>
  )
}
