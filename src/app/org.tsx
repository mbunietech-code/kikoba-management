import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { apiGet } from '@/lib/api'
import { setCurrency } from '@/lib/format'

export interface Organization {
  id?: string
  name: string
  currency: string
  logoUrl?: string | null
  registrationNumber?: string | null
  phone?: string | null
  email?: string | null
  address?: string | null
}

const FALLBACK: Organization = { name: 'Benja Kikoba', currency: 'TZS' }
const CACHE_KEY = 'bk.org'

function loadCache(): Organization {
  try {
    const raw = localStorage.getItem(CACHE_KEY)
    return raw ? { ...FALLBACK, ...JSON.parse(raw) } : FALLBACK
  } catch {
    return FALLBACK
  }
}

const OrgContext = createContext<{ org: Organization; refresh: () => Promise<void>; setOrg: (o: Organization) => void }>({
  org: FALLBACK,
  refresh: async () => {},
  setOrg: () => {},
})

export function OrgProvider({ children }: { children: ReactNode }) {
  const [org, setOrgState] = useState<Organization>(loadCache)

  const apply = useCallback((o: Organization) => {
    setOrgState(o)
    setCurrency(o.currency || 'TZS')
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify(o))
    } catch {
      /* ignore */
    }
  }, [])

  const refresh = useCallback(async () => {
    try {
      const o = await apiGet<Organization>('/organization')
      apply({ ...FALLBACK, ...o })
    } catch {
      /* keep cached / fallback */
    }
  }, [apply])

  useEffect(() => {
    setCurrency(org.currency || 'TZS')
    refresh()
  }, [])

  const value = useMemo(() => ({ org, refresh, setOrg: apply }), [org, refresh, apply])

  return <OrgContext.Provider value={value}>{children}</OrgContext.Provider>
}

export function useOrg() {
  return useContext(OrgContext)
}
