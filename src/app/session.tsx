import { createContext, useCallback, useContext, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import type { Role, Session } from '@/types'
import { CURRENT_MEMBER_ID, memberName } from '@/mock/data'
import { staffUsers } from '@/mock/data'

const KEY = 'kikoba.session'

function load(): Session | null {
  try {
    const raw = localStorage.getItem(KEY)
    return raw ? (JSON.parse(raw) as Session) : null
  } catch {
    return null
  }
}

interface SessionContextValue {
  session: Session | null
  signIn: (role: Role) => void
  signOut: () => void
  isStaff: boolean
}

const SessionContext = createContext<SessionContextValue>({
  session: null,
  signIn: () => {},
  signOut: () => {},
  isStaff: false,
})

export function SessionProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(load)

  const signIn = useCallback((role: Role) => {
    const next: Session =
      role === 'member'
        ? { role, name: memberName(CURRENT_MEMBER_ID), memberId: CURRENT_MEMBER_ID }
        : { role, name: staffUsers.find((u) => u.role === role)?.name ?? 'Staff User' }
    localStorage.setItem(KEY, JSON.stringify(next))
    setSession(next)
  }, [])

  const signOut = useCallback(() => {
    localStorage.removeItem(KEY)
    setSession(null)
  }, [])

  const value = useMemo<SessionContextValue>(
    () => ({ session, signIn, signOut, isStaff: !!session && session.role !== 'member' }),
    [session, signIn, signOut],
  )

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
}

export function useSession() {
  return useContext(SessionContext)
}
