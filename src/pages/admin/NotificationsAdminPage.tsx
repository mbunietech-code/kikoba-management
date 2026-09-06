import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Bell, CheckCheck, Mail, MessageSquare, Send, Smartphone, Trash2 } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, CardHeader, Field, Modal, PageHeader, Select, Textarea, Input, useToast, EmptyState,
} from '@/components/ui'
import { useApiQuery } from '@/lib/useApi'
import { deleteNotification, listNotifications, markAllNotificationsRead, sendAnnouncement } from '@/api'
import { relativeTime } from '@/lib/format'
import { cn } from '@/lib/cn'

const channelIcon: Record<string, typeof Bell> = {
  push: Smartphone, sms: MessageSquare, email: Mail, whatsapp: MessageSquare, in_app: Bell,
}

export default function NotificationsAdminPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { data, loading, error, refetch } = useApiQuery(() => listNotifications({ per_page: 100 }), [])
  const items: any[] = data?.data ?? []
  const unread = items.filter((n) => !n.read).length

  const [open, setOpen] = useState(false)
  const [form, setForm] = useState({ title: '', message: '', channel: 'in_app' })
  const [busy, setBusy] = useState(false)

  async function send() {
    setBusy(true)
    try {
      await sendAnnouncement(form)
      toast(t('common.submit') + ' ✓')
      setOpen(false)
      setForm({ title: '', message: '', channel: 'in_app' })
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  async function act(fn: () => Promise<unknown>, msg: string) {
    try {
      await fn()
      toast(msg)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  return (
    <>
      <PageHeader
        title={t('notifications.title')}
        subtitle={t('notifications.subtitle')}
        actions={
          <>
            <Button variant="outlined" leftIcon={<CheckCheck className="h-4 w-4" />} onClick={() => act(() => markAllNotificationsRead(), t('notifications.markAllRead') + ' ✓')}>
              {t('notifications.markAllRead')}
            </Button>
            <Button leftIcon={<Send className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('notifications.sendAnnouncement')}</Button>
          </>
        }
      />

      <Card>
        <CardHeader title={t('notifications.title')} subtitle={`${unread} ${t('notifications.unread')}`} />
        {error ? (
          <EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} />
        ) : loading ? (
          <EmptyState title={t('common.loading')} />
        ) : (
          <CardBody className="!p-0">
            <ul className="divide-y divide-neutral-100">
              {items.map((n, i) => {
                const Icon = channelIcon[n.channel] ?? Bell
                return (
                  <motion.li key={n.id} initial={{ opacity: 0, x: -8 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: Math.min(i * 0.03, 0.3) }} className={cn('flex gap-3 px-5 py-4', !n.read && 'bg-primary-50/40')}>
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
                    <button
                      className="self-center rounded-lg p-1.5 text-neutral-300 hover:bg-red-50 hover:text-danger"
                      onClick={() => act(() => deleteNotification(n.id), t('common.deleted'))}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </motion.li>
                )
              })}
            </ul>
          </CardBody>
        )}
      </Card>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('notifications.sendAnnouncement')}
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button leftIcon={<Send className="h-4 w-4" />} loading={busy} onClick={send}>{t('common.submit')}</Button>
          </>
        }
      >
        <div className="grid gap-4">
          <Field label={t('common.name')}><Input value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} placeholder="Announcement title" /></Field>
          <Field label={t('common.description')}><Textarea value={form.message} onChange={(e) => setForm((f) => ({ ...f, message: e.target.value }))} placeholder="Message to members…" /></Field>
          <Field label={t('notifications.channel')}>
            <Select value={form.channel} onChange={(e) => setForm((f) => ({ ...f, channel: e.target.value }))}>
              {(['in_app', 'sms', 'email', 'push', 'whatsapp'] as const).map((c) => (
                <option key={c} value={c}>{t(`notifications.channels.${c}`)}</option>
              ))}
            </Select>
          </Field>
        </div>
      </Modal>
    </>
  )
}
