import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowRight } from 'lucide-react'
import { AuthLayout } from './AuthLayout'
import { Button, Field, Input } from '@/components/ui'
import { DEMO_ACCOUNTS, useSession } from '@/app/session'
import type { Role } from '@/types'

const DEMO_ROLES: { role: Role; labelKey: string }[] = [
  { role: 'admin', labelKey: 'users.roles.admin' },
  { role: 'treasurer', labelKey: 'users.roles.treasurer' },
  { role: 'loan_officer', labelKey: 'users.roles.loan_officer' },
  { role: 'member', labelKey: 'users.roles.member' },
]

export default function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { signIn } = useSession()
  const [login, setLogin] = useState('admin@kikoba.co.tz')
  const [password, setPassword] = useState('demo1234')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      await signIn(login, password)
      navigate('/')
    } catch (err: any) {
      if (err?.code === 'OTP_REQUIRED') {
        navigate('/verify-otp')
        return
      }
      setError(err?.message ?? t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout>
      <h1 className="font-display text-2xl font-bold text-neutral-900">{t('auth.signIn')}</h1>

      <form onSubmit={submit} className="mt-6 flex flex-col gap-4">
        <Field label={t('auth.emailOrPhone')}>
          <Input
            type="text"
            value={login}
            onChange={(e) => setLogin(e.target.value)}
            autoComplete="username"
          />
        </Field>
        <Field label={t('auth.password')} error={error ?? undefined}>
          <Input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="current-password"
          />
        </Field>

        <div className="flex items-center justify-between text-[13px]">
          <label className="flex items-center gap-2 text-neutral-600">
            <input type="checkbox" className="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-200" defaultChecked />
            {t('auth.rememberMe')}
          </label>
          <button type="button" onClick={() => navigate('/forgot-password')} className="font-medium text-primary-700 hover:underline">
            {t('auth.forgotPassword')}
          </button>
        </div>

        <Button type="submit" size="lg" loading={loading} rightIcon={<ArrowRight className="h-4 w-4" />}>
          {t('auth.signIn')}
        </Button>
      </form>

      <div className="mt-5 rounded-xl bg-neutral-100 p-3">
        <p className="mb-2 text-center text-[12px] text-neutral-500">Demo accounts · password <code className="font-mono">demo1234</code></p>
        <div className="flex flex-wrap justify-center gap-1.5">
          {DEMO_ROLES.map((d) => (
            <button
              key={d.role}
              type="button"
              onClick={() => { setLogin(DEMO_ACCOUNTS[d.role]); setPassword('demo1234') }}
              className="rounded-lg bg-white px-2.5 py-1 text-[12px] font-medium text-neutral-600 ring-1 ring-neutral-200 hover:ring-primary-300"
            >
              {t(d.labelKey)}
            </button>
          ))}
        </div>
      </div>

      <p className="mt-4 text-center text-[13px] text-neutral-500">
        {t('auth.noAccount')} <span className="font-medium text-neutral-700">{t('auth.contactAdmin')}</span>
      </p>
    </AuthLayout>
  )
}
