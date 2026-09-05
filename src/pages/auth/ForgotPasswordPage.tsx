import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, MailCheck } from 'lucide-react'
import { AuthLayout } from './AuthLayout'
import { Button, Field, Input } from '@/components/ui'

export default function ForgotPasswordPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [sent, setSent] = useState(false)

  return (
    <AuthLayout>
      {sent ? (
        <div className="text-center">
          <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-tertiary-50 text-tertiary-600">
            <MailCheck className="h-6 w-6" />
          </div>
          <h1 className="font-display text-xl font-bold text-neutral-900">{t('auth.sendResetLink')}</h1>
          <p className="mt-2 text-sm text-neutral-500">
            {t('auth.forgotSubtitle')}
          </p>
          <Button variant="outlined" className="mt-6 w-full" onClick={() => navigate('/login')}>
            {t('auth.backToSignIn')}
          </Button>
        </div>
      ) : (
        <>
          <button onClick={() => navigate('/login')} className="mb-4 inline-flex items-center gap-1.5 text-[13px] font-medium text-neutral-500 hover:text-neutral-800">
            <ArrowLeft className="h-4 w-4" /> {t('auth.backToSignIn')}
          </button>
          <h1 className="font-display text-2xl font-bold text-neutral-900">{t('auth.forgotTitle')}</h1>
          <p className="mt-1.5 text-sm text-neutral-500">{t('auth.forgotSubtitle')}</p>
          <form
            className="mt-6 flex flex-col gap-4"
            onSubmit={(e) => {
              e.preventDefault()
              setSent(true)
            }}
          >
            <Field label={t('common.email')}>
              <Input type="email" placeholder="you@example.co.tz" required />
            </Field>
            <Button type="submit" size="lg">
              {t('auth.sendResetLink')}
            </Button>
          </form>
        </>
      )}
    </AuthLayout>
  )
}
