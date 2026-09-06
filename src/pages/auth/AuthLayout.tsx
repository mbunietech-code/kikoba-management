import type { ReactNode } from 'react'
import { motion } from 'motion/react'
import { useTranslation } from 'react-i18next'
import { LanguageToggle } from '@/components/LanguageToggle'
import { useOrg } from '@/app/org'

export function AuthLayout({ children }: { children: ReactNode }) {
  const { t } = useTranslation()
  const { org } = useOrg()
  const initials = org.name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]?.toUpperCase()).join('') || 'BK'
  return (
    <div className="flex min-h-screen bg-neutral-100">
      {/* brand panel */}
      <div className="relative hidden w-[46%] overflow-hidden bg-primary-900 lg:block">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(45,212,191,0.25),transparent_45%),radial-gradient(circle_at_80%_80%,rgba(37,99,235,0.25),transparent_45%)]" />
        <div className="relative flex h-full flex-col justify-between p-12 text-white">
          <div className="flex items-center gap-3">
            <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 font-display text-base font-bold tracking-tight backdrop-blur">
              {initials}
            </span>
            <div>
              <p className="font-display text-lg font-bold">{org.name}</p>
              <p className="text-sm text-white/60">{t('app.tagline')}</p>
            </div>
          </div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.1 }}
          >
            <h2 className="max-w-md font-display text-3xl font-bold leading-tight">
              One platform for members, shares, savings, loans, projects & protection.
            </h2>
            <p className="mt-4 max-w-md text-white/70">
              Every shilling in or out carries a source, a reference, an accounting record and an audit trail.
            </p>
          </motion.div>

          <div className="grid grid-cols-3 gap-4 text-sm">
            {[
              ['Double-entry', 'accounting'],
              ['Audit trail', 'on every action'],
              ['Multi-org', 'ready'],
            ].map(([a, b]) => (
              <div key={a} className="rounded-xl bg-white/10 p-3 backdrop-blur">
                <p className="font-semibold">{a}</p>
                <p className="text-white/60">{b}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* form panel */}
      <div className="flex flex-1 flex-col">
        <div className="flex justify-end p-5">
          <LanguageToggle />
        </div>
        <div className="flex flex-1 items-center justify-center px-5 pb-16">
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.35, ease: [0.16, 1, 0.3, 1] }}
            className="w-full max-w-sm"
          >
            {children}
          </motion.div>
        </div>
      </div>
    </div>
  )
}
