import { useEffect, useRef, useState } from 'react'

interface QueryState<T> {
  data: T | undefined
  loading: boolean
  error: Error | undefined
  refetch: () => void
}

/**
 * Simulates an async data fetch against the local mock dataset so pages can
 * exercise real loading / empty / error states. Delay is short and deterministic.
 */
export function useMockQuery<T>(resolver: () => T, deps: unknown[] = [], delay = 320): QueryState<T> {
  const [state, setState] = useState<QueryState<T>>({
    data: undefined,
    loading: true,
    error: undefined,
    refetch: () => {},
  })
  const tick = useRef(0)

  useEffect(() => {
    let cancelled = false
    setState((s) => ({ ...s, loading: true, error: undefined }))
    const id = setTimeout(() => {
      if (cancelled) return
      try {
        setState({ data: resolver(), loading: false, error: undefined, refetch: () => tick.current++ })
      } catch (e) {
        setState({ data: undefined, loading: false, error: e as Error, refetch: () => tick.current++ })
      }
    }, delay)
    return () => {
      cancelled = true
      clearTimeout(id)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps)

  return state
}
