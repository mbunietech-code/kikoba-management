/**
 * Thin API client for the Benja Kikoba Laravel backend.
 * - attaches the bearer access token
 * - unwraps the { success, data, meta } envelope
 * - transparently refreshes the access token once on 401
 */

const BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8001/api/v1'

const ACCESS_KEY = 'bk.access'
const REFRESH_KEY = 'bk.refresh'

export const tokenStore = {
  get access() {
    return localStorage.getItem(ACCESS_KEY)
  },
  get refresh() {
    return localStorage.getItem(REFRESH_KEY)
  },
  set(access: string, refresh?: string) {
    localStorage.setItem(ACCESS_KEY, access)
    if (refresh) localStorage.setItem(REFRESH_KEY, refresh)
  },
  clear() {
    localStorage.removeItem(ACCESS_KEY)
    localStorage.removeItem(REFRESH_KEY)
  },
}

export interface ApiError extends Error {
  code: string
  status: number
  fields?: Record<string, string[]>
}

function makeError(status: number, body: any): ApiError {
  const err = new Error(body?.error?.message ?? body?.message ?? 'Request failed') as ApiError
  err.code = body?.error?.code ?? 'ERROR'
  err.status = status
  err.fields = body?.error?.fields
  return err
}

let refreshing: Promise<boolean> | null = null

async function tryRefresh(): Promise<boolean> {
  if (refreshing) return refreshing
  const refresh = tokenStore.refresh
  if (!refresh) return false

  refreshing = (async () => {
    try {
      const res = await fetch(`${BASE}/auth/refresh`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: refresh }),
      })
      if (!res.ok) return false
      const body = await res.json()
      tokenStore.set(body.data.access_token, body.data.refresh_token)
      return true
    } catch {
      return false
    } finally {
      refreshing = null
    }
  })()

  return refreshing
}

type Options = {
  method?: string
  body?: unknown
  query?: Record<string, string | number | undefined | null>
  auth?: boolean
  _retry?: boolean
}

export async function api<T = any>(path: string, opts: Options = {}): Promise<T> {
  const { method = 'GET', body, query, auth = true, _retry = false } = opts

  // if a refresh is mid-flight, wait for it so we send the fresh token
  if (auth && refreshing && !_retry) {
    await refreshing
  }

  const url = new URL(`${BASE}${path}`)
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, String(v))
    }
  }

  const headers: Record<string, string> = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (auth && tokenStore.access) headers.Authorization = `Bearer ${tokenStore.access}`

  const res = await fetch(url.toString(), {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })

  if (res.status === 401 && auth && !_retry && (await tryRefresh())) {
    return api<T>(path, { ...opts, _retry: true })
  }

  const text = await res.text()
  const parsed = text ? JSON.parse(text) : {}

  if (!res.ok || parsed?.success === false) {
    if (res.status === 401) {
      tokenStore.clear()
      window.dispatchEvent(new Event('bk:signed-out'))
    }
    throw makeError(res.status, parsed)
  }

  return (parsed.meta ? { ...parsed } : parsed.data) as T
}

/** GET returning the `data` payload. */
export const apiGet = <T = any>(path: string, query?: Options['query']) => api<T>(path, { query })

/** GET returning `{ data, meta }` for paginated endpoints. */
export const apiList = <T = any>(path: string, query?: Options['query']) =>
  api<{ data: T[]; meta?: any }>(path, { query })

export const apiPost = <T = any>(path: string, body?: unknown) => api<T>(path, { method: 'POST', body })
export const apiPut = <T = any>(path: string, body?: unknown) => api<T>(path, { method: 'PUT', body })
export const apiPatch = <T = any>(path: string, body?: unknown) => api<T>(path, { method: 'PATCH', body })
export const apiDelete = <T = any>(path: string) => api<T>(path, { method: 'DELETE' })
