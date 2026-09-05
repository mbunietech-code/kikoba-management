import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { AuthLayout } from './AuthLayout'
import { Button, Field, Input } from '@/components/ui'
import { useToast } from '@/components/ui'

export default function ResetPasswordPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()

  return (
    <AuthLayout>
      <h1 className="font-display text-2xl font-bold text-neutral-900">{t('auth.resetTitle')}</h1>
      <form
        className="mt-6 flex flex-col gap-4"
        onSubmit={(e) => {
          e.preventDefault()
          toast(t('auth.updatePassword'))
          navigate('/login')
        }}
      >
        <Field label={t('auth.newPassword')}>
          <Input type="password" required minLength={8} />
        </Field>
        <Field label={t('auth.confirmPassword')}>
          <Input type="password" required minLength={8} />
        </Field>
        <Button type="submit" size="lg">
          {t('auth.updatePassword')}
        </Button>
      </form>
    </AuthLayout>
  )
}
