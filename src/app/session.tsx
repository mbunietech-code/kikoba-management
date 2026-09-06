import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import type { Role, Session } from '@/types'
import { api, apiPost, tokenStore } from '@/lib/api'

const SESSION_KEY = 'bk.session'

/** Demo accounts seeded by the backend (DemoSeeder). Password: demo1234 */
export const DEMO_ACCOUNTS: Record<Role, string> = {
  super_admin: 'super.admin@kikoba.co.tz',
  admin: 'admin@kikoba.co.tz',
  treasurer: 'treasurer@kikoba.co.tz',
  accountant: 'accountant@kikoba.co.tz',
  loan_officer: 'officer@kikoba.co.tz',
  member: 'member@mfano.co.tz',
}

function loadCached(): Session | null {
  try {
    const raw = localStorage.getItem(SESSION_KEY)
    return raw ? (JSON.parse(raw) as Session) : null
  } catch {
    return null
  }
}

interface SessionContextValue {
  session: Session | null
  loading: boolean
  error: string | null
  signIn: (login: string, password: string) => Promise<void>
  previewAs: (role: Role) => Promise<void>
  signOut: () => void
  isStaff: boolean
}

const SessionContext = createContext<SessionContextValue>({
  session: null,
  loading: false,
  error: null,
  signIn: async () => {},
  previewAs: async () => {},
  signOut: () => {},
  isStaff: false,
})

function toSession(user: any): Session {
  return {
    role: (user.role ?? user.roles?.[0] ?? 'member') as Role,
    name: user.name,
    memberId: user.member_id ?? user.memberId ?? undefined,
  }
}

export function SessionProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(loadCached)
  const [loading, setLoading] = useState<boolean>(!!tokenStore.access)
  const [error, setError] = useState<string | null>(null)

  // validate the cached token on boot
  useEffect(() => {
    if (!tokenStore.access) {
      setLoading(false)
      return
    }
    api('/auth/me')
      .then((user) => {
        const s = toSession(user)
        setSession(s)
        localStorage.setItem(SESSION_KEY, JSON.stringify(s))
      })
      .catch(() => {
        tokenStore.clear()
        localStorage.removeItem(SESSION_KEY)
        setSession(null)
      })
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    const onSignedOut = () => {
      localStorage.removeItem(SESSION_KEY)
      setSession(null)
    }
    window.addEventListener('bk:signed-out', onSignedOut)
    return () => window.removeEventListener('bk:signed-out', onSignedOut)
  }, [])

  const signIn = useCallback(async (login: string, password: string) => {
    setError(null)
    setLoading(true)
    try {
      const res = await apiPost('/auth/login', { login, password })
      if (res.otp_required) {
        // OTP flow — store destination for the verify screen
        sessionStorage.setItem('bk.otp', JSON.stringify({ destination: res.destination, debug: res.debug_code }))
        throw Object.assign(new Error('OTP_REQUIRED'), { code: 'OTP_REQUIRED', destination: res.destination })
      }
      tokenStore.set(res.access_token, res.refresh_token)
      const s = toSession(res.user)
      setSession(s)
      localStorage.setItem(SESSION_KEY, JSON.stringify(s))
    } catch (e: any) {
      if (e.code !== 'OTP_REQUIRED') setError(e.message ?? 'Sign in failed')
      throw e
    } finally {
      setLoading(false)
    }
  }, [])

  const previewAs = useCallback(
    async (role: Role) => {
      await signIn(DEMO_ACCOUNTS[role], 'demo1234')
    },
    [signIn],
  )

  const signOut = useCallback(() => {
    apiPost('/auth/logout').catch(() => {})
    tokenStore.clear()
    localStorage.removeItem(SESSION_KEY)
    setSession(null)
  }, [])

  const value = useMemo<SessionContextValue>(
    () => ({
      session,
      loading,
      error,
      signIn,
      previewAs,
      signOut,
      isStaff: !!session && session.role !== 'member',
    }),
    [session, loading, error, signIn, previewAs, signOut],
  )

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
}

export function useSession() {
  return useContext(SessionContext)
}
