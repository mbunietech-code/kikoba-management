import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft } from 'lucide-react'
import { Button, Card, CardBody, CardHeader, Field, Input, PageHeader, Select, useToast, EmptyState } from '@/components/ui'
import { useApiQuery } from '@/lib/useApi'
import { createMember, getMember, updateMember } from '@/api'

export default function MemberFormPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const { id } = useParams()
  const editing = !!id

  const { data: existing, loading } = useApiQuery(() => (id ? getMember(id) : Promise.resolve(null)), [id])

  const [form, setForm] = useState<any>(null)
  const [busy, setBusy] = useState(false)

  // hydrate form once data arrives (or immediately for create)
  if (form === null && !loading) {
    setForm({
      full_name: existing?.fullName ?? '',
      phone: existing?.phone ?? '',
      email: existing?.email ?? '',
      gender: existing?.gender ?? 'female',
      date_of_birth: existing?.dateOfBirth?.slice(0, 10) ?? '',
      address: existing?.address ?? '',
      next_of_kin: existing?.nextOfKin ?? '',
      next_of_kin_phone: existing?.nextOfKinPhone ?? '',
      status: existing?.status ?? 'pending',
    })
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setBusy(true)
    try {
      const body = { ...form }
      if (!body.date_of_birth) delete body.date_of_birth
      if (!body.email) delete body.email
      if (editing) {
        await updateMember(id!, body)
        toast(`${t('common.saveChanges')} ✓`)
        navigate(`/admin/members/${id}`)
      } else {
        const created = await createMember(body)
        toast(`${t('members.addMember')} ✓`)
        navigate(`/admin/members/${created?.id ?? ''}`)
      }
    } catch (err: any) {
      toast(err?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  if (loading || !form) return <div className="card p-10"><EmptyState title={t('common.loading')} /></div>

  const F = (label: string, key: string, type = 'text', span = false) => (
    <Field label={label} className={span ? 'sm:col-span-2' : undefined}>
      <Input type={type} value={form[key] ?? ''} onChange={(e) => setForm((f: any) => ({ ...f, [key]: e.target.value }))} />
    </Field>
  )

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
            {F(t('members.fullName'), 'full_name', 'text', true)}
            {F(t('common.phone'), 'phone')}
            {F(t('common.email'), 'email', 'email')}
            <Field label={t('members.gender')}>
              <Select value={form.gender} onChange={(e) => setForm((f: any) => ({ ...f, gender: e.target.value }))}>
                <option value="female">{t('members.female')}</option>
                <option value="male">{t('members.male')}</option>
                <option value="other">{t('members.other')}</option>
              </Select>
            </Field>
            {F(t('members.dob'), 'date_of_birth', 'date')}
            {F(t('members.address'), 'address', 'text', true)}
          </CardBody>
        </Card>

        <div className="flex flex-col gap-4">
          <Card>
            <CardHeader title={t('members.nextOfKin')} />
            <CardBody className="grid gap-4">
              {F(t('common.name'), 'next_of_kin')}
              {F(t('members.nextOfKinPhone'), 'next_of_kin_phone')}
            </CardBody>
          </Card>
          <Card>
            <CardHeader title={t('common.status')} />
            <CardBody>
              <Field label={t('common.status')}>
                <Select value={form.status} onChange={(e) => setForm((f: any) => ({ ...f, status: e.target.value }))}>
                  {(['pending', 'active', 'suspended', 'inactive', 'deceased'] as const).map((s) => (
                    <option key={s} value={s}>{t(`members.status.${s}`)}</option>
                  ))}
                </Select>
              </Field>
            </CardBody>
          </Card>

          <div className="flex gap-2">
            <Button type="button" variant="outlined" className="flex-1" onClick={() => navigate(-1)}>{t('common.cancel')}</Button>
            <Button type="submit" className="flex-1" loading={busy}>{editing ? t('common.saveChanges') : t('common.create')}</Button>
          </div>
        </div>
      </form>
    </>
  )
}
