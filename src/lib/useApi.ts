import { useCallback, useEffect, useRef, useState } from 'react'
import { api, type ApiError } from './api'

interface QueryState<T> {
  data: T | undefined
  loading: boolean
  error: ApiError | undefined
  refetch: () => void
}

/**
 * Drop-in async query hook backed by the real API.
 * `key` should change when the request should re-run.
 */
export function useApiQuery<T>(
  fetcher: () => Promise<T>,
  deps: unknown[] = [],
): QueryState<T> {
  const [data, setData] = useState<T | undefined>(undefined)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<ApiError | undefined>(undefined)
  const [tick, setTick] = useState(0)
  const fetcherRef = useRef(fetcher)
  fetcherRef.current = fetcher

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(undefined)
    fetcherRef
      .current()
      .then((res) => {
        if (!cancelled) setData(res)
      })
      .catch((e) => {
        if (!cancelled) setError(e as ApiError)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [...deps, tick])

  const refetch = useCallback(() => setTick((t) => t + 1), [])

  return { data, loading, error, refetch }
}

export { api }
