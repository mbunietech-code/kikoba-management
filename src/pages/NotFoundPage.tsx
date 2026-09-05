import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui'

export default function NotFoundPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-neutral-100 px-6 text-center">
      <p className="font-display text-7xl font-bold text-primary-800">404</p>
      <h1 className="mt-4 font-display text-xl font-bold text-neutral-900">{t('errors.notFound')}</h1>
      <p className="mt-2 max-w-sm text-sm text-neutral-500">{t('errors.notFoundHint')}</p>
      <Button className="mt-6" onClick={() => navigate('/')}>
        {t('errors.goHome')}
      </Button>
    </div>
  )
}
