import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'
import { useSession } from './session'

export function RoleHome() {
  const { session } = useSession()
  if (!session) return <Navigate to="/login" replace />
  return <Navigate to={session.role === 'member' ? '/member' : '/admin'} replace />
}

export function RequireAuth({ children, staff = false }: { children: ReactNode; staff?: boolean }) {
  const { session } = useSession()
  if (!session) return <Navigate to="/login" replace />
  if (staff && session.role === 'member') return <Navigate to="/member" replace />
  if (!staff && session.role !== 'member') return <Navigate to="/admin" replace />
  return <>{children}</>
}
