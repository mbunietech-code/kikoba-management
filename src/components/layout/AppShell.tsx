import { useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { AnimatePresence, motion } from 'motion/react'
import { Sidebar } from './Sidebar'
import { Topbar } from './Topbar'
import { adminNav, memberNav } from '@/app/nav'
import { useSession } from '@/app/session'

export function AppShell() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const { isStaff } = useSession()
  const location = useLocation()
  const sections = isStaff ? adminNav : memberNav

  return (
    <div className="flex min-h-screen bg-neutral-100">
      {/* desktop sidebar */}
      <aside className="hidden w-64 shrink-0 border-r border-neutral-200 bg-white lg:block">
        <div className="sticky top-0 h-screen">
          <Sidebar sections={sections} />
        </div>
      </aside>

      {/* mobile drawer */}
      <AnimatePresence>
        {mobileOpen && (
          <div className="fixed inset-0 z-40 lg:hidden">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="absolute inset-0 bg-neutral-950/40 backdrop-blur-sm"
              onClick={() => setMobileOpen(false)}
            />
            <motion.aside
              initial={{ x: '-100%' }}
              animate={{ x: 0 }}
              exit={{ x: '-100%' }}
              transition={{ type: 'spring', stiffness: 380, damping: 40 }}
              className="absolute inset-y-0 left-0 w-72 bg-white shadow-xl"
            >
              <Sidebar sections={sections} onNavigate={() => setMobileOpen(false)} />
            </motion.aside>
          </div>
        )}
      </AnimatePresence>

      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar onOpenSidebar={() => setMobileOpen(true)} />
        <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-[1200px]">
            <AnimatePresence mode="wait">
              <motion.div
                key={location.pathname}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                transition={{ duration: 0.22, ease: [0.16, 1, 0.3, 1] }}
              >
                <Outlet />
              </motion.div>
            </AnimatePresence>
          </div>
        </main>
      </div>
    </div>
  )
}
