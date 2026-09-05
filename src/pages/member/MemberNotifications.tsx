import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Bell, CheckCheck, Mail, MessageSquare, Smartphone } from 'lucide-react'
import { Badge, Button, Card, CardBody, CardHeader, PageHeader } from '@/components/ui'
import { relativeTime } from '@/lib/format'
import { notifications as seed } from '@/mock/data'
import type { NotificationChannel } from '@/types'
import { cn } from '@/lib/cn'

const channelIcon: Record<NotificationChannel, typeof Bell> = {
  push: Smartphone, sms: MessageSquare, email: Mail, whatsapp: MessageSquare, in_app: Bell,
}

export default function MemberNotifications() {
  const { t } = useTranslation()
  const [items, setItems] = useState(seed)
  const unread = items.filter((n) => !n.read).length

  return (
    <>
      <PageHeader
        title={t('nav.notifications')}
        subtitle={`${unread} ${t('notifications.unread')}`}
        actions={
          <Button variant="outlined" leftIcon={<CheckCheck className="h-4 w-4" />} onClick={() => setItems((x) => x.map((n) => ({ ...n, read: true })))}>
            {t('notifications.markAllRead')}
          </Button>
        }
      />

      <Card>
        <CardHeader title={t('nav.notifications')} />
        <CardBody className="!p-0">
          <ul className="divide-y divide-neutral-100">
            {items.map((n, i) => {
              const Icon = channelIcon[n.channel]
              return (
                <motion.li
                  key={n.id}
                  initial={{ opacity: 0, x: -8 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: Math.min(i * 0.03, 0.3) }}
                  className={cn('flex gap-3 px-5 py-4', !n.read && 'bg-primary-50/40')}
                >
                  <span className={cn('mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl', n.read ? 'bg-neutral-100 text-neutral-400' : 'bg-primary-100 text-primary-700')}>
                    <Icon className="h-4 w-4" />
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="text-[14px] font-semibold text-neutral-900">{n.title}</p>
                    <p className="mt-0.5 text-[13px] text-neutral-600">{n.message}</p>
                    <div className="mt-1.5 flex items-center gap-2">
                      <Badge tone="neutral">{t(`notifications.channels.${n.channel}`)}</Badge>
                      <span className="text-[12px] text-neutral-400">{relativeTime(n.createdAt)}</span>
                    </div>
                  </div>
                </motion.li>
              )
            })}
          </ul>
        </CardBody>
      </Card>
    </>
  )
}
