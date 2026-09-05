import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Bell, LogOut, Menu as MenuIcon, Search, UserRound } from 'lucide-react'
import { LanguageToggle } from '@/components/LanguageToggle'
import { Avatar, Menu, MenuItem } from '@/components/ui'
import { useSession } from '@/app/session'
import { notifications } from '@/mock/data'

export function Topbar({ onOpenSidebar }: { onOpenSidebar: () => void }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { session, signOut, isStaff } = useSession()
  const unread = notifications.filter((n) => !n.read).length

  return (
    <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-neutral-200 bg-white/85 px-4 backdrop-blur-md sm:px-6">
      <button
        onClick={onOpenSidebar}
        className="rounded-lg p-2 text-neutral-500 hover:bg-neutral-100 lg:hidden"
        aria-label="Open menu"
      >
        <MenuIcon className="h-5 w-5" />
      </button>

      <div className="relative hidden max-w-xs flex-1 sm:block">
        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
        <input
          placeholder={t('common.searchPlaceholder')}
          className="h-9 w-full rounded-lg border border-neutral-200 bg-neutral-50 pl-9 pr-3 text-sm text-neutral-700 placeholder:text-neutral-400 focus:border-primary-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-100"
        />
      </div>

      <div className="ml-auto flex items-center gap-2">
        <LanguageToggle />

        <button
          onClick={() => navigate(isStaff ? '/admin/notifications' : '/member/notifications')}
          className="relative rounded-lg p-2 text-neutral-500 hover:bg-neutral-100"
          aria-label="Notifications"
        >
          <Bell className="h-5 w-5" />
          {unread > 0 && (
            <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold text-white">
              {unread}
            </span>
          )}
        </button>

        <Menu
          trigger={
            <span className="flex items-center gap-2 rounded-lg py-1 pl-1 pr-2 hover:bg-neutral-100">
              <Avatar name={session?.name ?? 'User'} size="sm" color="#0f172a" />
              <span className="hidden text-left sm:block">
                <span className="block text-[13px] font-semibold leading-tight text-neutral-800">{session?.name}</span>
                <span className="block text-[11px] capitalize leading-tight text-neutral-400">
                  {t(`users.roles.${session?.role}`)}
                </span>
              </span>
            </span>
          }
        >
          {(close) => (
            <>
              <MenuItem
                icon={<UserRound className="h-4 w-4" />}
                onClick={() => {
                  close()
                  navigate(isStaff ? '/admin/settings' : '/member/profile')
                }}
              >
                {t('common.profile')}
              </MenuItem>
              <MenuItem
                danger
                icon={<LogOut className="h-4 w-4" />}
                onClick={() => {
                  close()
                  signOut()
                  navigate('/login')
                }}
              >
                {t('common.logout')}
              </MenuItem>
            </>
          )}
        </Menu>
      </div>
    </header>
  )
}
