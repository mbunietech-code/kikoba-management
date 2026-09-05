import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowRight, Building2, UserRound } from 'lucide-react'
import { AuthLayout } from './AuthLayout'
import { Button, Field, Input } from '@/components/ui'
import { useSession } from '@/app/session'
import type { Role } from '@/types'
import { cn } from '@/lib/cn'

export default function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { signIn } = useSession()
  const [role, setRole] = useState<Role>('admin')
  const [loading, setLoading] = useState(false)

  function submit(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setTimeout(() => {
      signIn(role)
      navigate(role === 'member' ? '/member' : '/admin')
    }, 500)
  }

  return (
    <AuthLayout>
      <h1 className="font-display text-2xl font-bold text-neutral-900">{t('auth.signIn')}</h1>
      <p className="mt-1.5 text-sm text-neutral-500">{t('auth.signInSubtitle')}</p>

      <div className="mt-5 grid grid-cols-2 gap-2">
        {(
          [
            { value: 'admin', label: t('auth.roleAdmin'), icon: Building2 },
            { value: 'member', label: t('auth.roleMember'), icon: UserRound },
          ] as const
        ).map((opt) => (
          <button
            key={opt.value}
            type="button"
            onClick={() => setRole(opt.value)}
            className={cn(
              'flex flex-col items-start gap-2 rounded-xl border p-3 text-left transition-all',
              role === opt.value
                ? 'border-primary-500 bg-primary-50 ring-4 ring-primary-100'
                : 'border-neutral-200 hover:border-neutral-300',
            )}
          >
            <opt.icon className={cn('h-5 w-5', role === opt.value ? 'text-primary-700' : 'text-neutral-400')} />
            <span className="text-[13px] font-medium text-neutral-700">{opt.label}</span>
          </button>
        ))}
      </div>

      <form onSubmit={submit} className="mt-5 flex flex-col gap-4">
        <Field label={t('auth.emailOrPhone')}>
          <Input type="text" defaultValue={role === 'member' ? 'member@mfano.co.tz' : 'admin@kikoba.co.tz'} autoComplete="username" />
        </Field>
        <Field label={t('auth.password')}>
          <Input type="password" defaultValue="demo1234" autoComplete="current-password" />
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

      <p className="mt-5 rounded-xl bg-neutral-100 p-3 text-center text-[12px] text-neutral-500">{t('auth.demoNote')}</p>

      <p className="mt-4 text-center text-[13px] text-neutral-500">
        {t('auth.noAccount')} <span className="font-medium text-neutral-700">{t('auth.contactAdmin')}</span>
      </p>
    </AuthLayout>
  )
}
