import { NavLink } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import type { NavSection } from '@/app/nav'
import { useSession } from '@/app/session'
import { cn } from '@/lib/cn'

export function Sidebar({ sections, onNavigate }: { sections: NavSection[]; onNavigate?: () => void }) {
  const { t } = useTranslation()
  const { session } = useSession()
  const role = session?.role

  return (
    <nav className="flex h-full flex-col gap-6 overflow-y-auto px-3 py-5">
      <div className="flex items-center gap-2.5 px-3">
        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-800 font-display text-[13px] font-bold tracking-tight text-white">
          BK
        </span>
        <div>
          <p className="font-display text-[15px] font-bold leading-tight text-neutral-900">{t('app.name')}</p>
          <p className="text-[11px] text-neutral-400">{t('app.tagline')}</p>
        </div>
      </div>

      <div className="flex flex-1 flex-col gap-5">
        {sections.map((section) => {
          const items = section.items.filter((it) => !it.roles || (role && it.roles.includes(role)))
          if (!items.length) return null
          return (
            <div key={section.titleKey}>
              <p className="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                {t(section.titleKey)}
              </p>
              <ul className="flex flex-col gap-0.5">
                {items.map((item) => (
                  <li key={item.to}>
                    <NavLink
                      to={item.to}
                      end={item.end}
                      onClick={onNavigate}
                      className={({ isActive }) =>
                        cn(
                          'group relative flex items-center gap-3 rounded-xl px-3 py-2 text-[13.5px] font-medium transition-colors',
                          isActive
                            ? 'bg-primary-50 text-primary-800'
                            : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900',
                        )
                      }
                    >
                      {({ isActive }) => (
                        <>
                          {isActive && (
                            <motion.span
                              layoutId="nav-active"
                              className="absolute inset-y-1 left-0 w-1 rounded-r-full bg-primary-700"
                              transition={{ type: 'spring', stiffness: 400, damping: 32 }}
                            />
                          )}
                          <item.icon className={cn('h-[18px] w-[18px] shrink-0', isActive ? 'text-primary-700' : 'text-neutral-400 group-hover:text-neutral-600')} />
                          {t(item.labelKey)}
                        </>
                      )}
                    </NavLink>
                  </li>
                ))}
              </ul>
            </div>
          )
        })}
      </div>
    </nav>
  )
}
