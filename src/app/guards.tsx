import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'
import { useSession } from './session'

function Splash() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-neutral-100">
      <div className="flex flex-col items-center gap-3">
        <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-800 font-display text-sm font-bold text-white">
          BK
        </span>
        <span className="h-5 w-5 animate-spin rounded-full border-2 border-neutral-300 border-t-primary-600" />
      </div>
    </div>
  )
}

export function RoleHome() {
  const { session, loading } = useSession()
  if (loading) return <Splash />
  if (!session) return <Navigate to="/login" replace />
  return <Navigate to={session.role === 'member' ? '/member' : '/admin'} replace />
}

export function RequireAuth({ children, staff = false }: { children: ReactNode; staff?: boolean }) {
  const { session, loading } = useSession()
  if (loading) return <Splash />
  if (!session) return <Navigate to="/login" replace />
  if (staff && session.role === 'member') return <Navigate to="/member" replace />
  if (!staff && session.role !== 'member') return <Navigate to="/admin" replace />
  return <>{children}</>
}
