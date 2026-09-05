import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Check } from 'lucide-react'
import { Badge, Card, CardBody, PageHeader, useToast } from '@/components/ui'
import { staffUsers } from '@/mock/data'
import type { Role } from '@/types'
import { cn } from '@/lib/cn'

const PERMISSIONS = [
  'members.view', 'members.create', 'members.update',
  'shares.view', 'shares.create',
  'savings.view', 'savings.deposit', 'savings.withdraw',
  'loans.view', 'loans.approve', 'loans.reject', 'loans.disburse',
  'payments.view', 'payments.verify',
  'accounting.view', 'accounting.post',
  'reports.view', 'settings.manage', 'users.manage', 'audit.view',
]

const ROLE_PERMS: Record<Role, string[] | 'all'> = {
  super_admin: 'all',
  admin: PERMISSIONS.filter((p) => !p.startsWith('accounting.post')),
  treasurer: ['savings.view', 'savings.deposit', 'savings.withdraw', 'payments.view', 'payments.verify', 'reports.view', 'members.view', 'loans.view'],
  accountant: ['accounting.view', 'accounting.post', 'reports.view', 'payments.view', 'members.view', 'loans.view', 'savings.view'],
  loan_officer: ['loans.view', 'loans.approve', 'loans.reject', 'members.view', 'reports.view'],
  member: ['members.view'],
}

const ROLES: Role[] = ['super_admin', 'admin', 'treasurer', 'accountant', 'loan_officer']

export default function RolesPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [active, setActive] = useState<Role>('admin')

  const has = (perm: string) => {
    const set = ROLE_PERMS[active]
    return set === 'all' || set.includes(perm)
  }

  return (
    <>
      <PageHeader title={t('roles.title')} subtitle={t('roles.subtitle')} />

      <div className="grid gap-4 lg:grid-cols-[260px_1fr]">
        <div className="flex flex-col gap-2">
          {ROLES.map((r) => {
            const count = staffUsers.filter((u) => u.role === r).length
            return (
              <button
                key={r}
                onClick={() => setActive(r)}
                className={cn(
                  'flex items-center justify-between rounded-xl border px-4 py-3 text-left transition-all',
                  active === r ? 'border-primary-500 bg-primary-50 ring-4 ring-primary-100' : 'border-neutral-200 bg-white hover:border-neutral-300',
                )}
              >
                <span>
                  <span className="block text-[14px] font-semibold text-neutral-800">{t(`users.roles.${r}`)}</span>
                  <span className="block text-[12px] text-neutral-400">{count} {t('roles.members')}</span>
                </span>
                {ROLE_PERMS[r] === 'all' && <Badge tone="primary">{t('common.all')}</Badge>}
              </button>
            )
          })}
        </div>

        <Card>
          <CardBody>
            <div className="mb-3 flex items-center justify-between">
              <h3 className="font-display text-[15px] font-bold text-neutral-900">{t('roles.permissions')} — {t(`users.roles.${active}`)}</h3>
              <button className="text-[13px] font-medium text-primary-700 hover:underline" onClick={() => toast(t('settings.saved'))}>
                {t('common.saveChanges')}
              </button>
            </div>
            <div className="grid gap-2 sm:grid-cols-2">
              {PERMISSIONS.map((p, i) => (
                <motion.label
                  key={p}
                  initial={{ opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: Math.min(i * 0.02, 0.2) }}
                  className="flex items-center gap-3 rounded-lg border border-neutral-150 border-neutral-200 px-3 py-2.5"
                >
                  <span className={cn('flex h-5 w-5 items-center justify-center rounded-md border', has(p) ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300')}>
                    {has(p) && <Check className="h-3.5 w-3.5" />}
                  </span>
                  <span className="font-mono text-[12.5px] text-neutral-600">{p}</span>
                </motion.label>
              ))}
            </div>
          </CardBody>
        </Card>
      </div>
    </>
  )
}
