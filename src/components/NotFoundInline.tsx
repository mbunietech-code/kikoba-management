import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { FileQuestion } from 'lucide-react'
import { Button, Card, EmptyState } from '@/components/ui'

export function NotFoundInline({ message }: { message?: string }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  return (
    <Card className="mt-6">
      <EmptyState
        icon={<FileQuestion className="h-6 w-6" />}
        title={t('errors.notFound')}
        hint={message ?? t('errors.notFoundHint')}
        action={
          <Button variant="outlined" onClick={() => navigate(-1)}>
            {t('common.back')}
          </Button>
        }
      />
    </Card>
  )
}
