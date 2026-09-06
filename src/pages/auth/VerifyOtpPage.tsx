import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { AuthLayout } from './AuthLayout'
import { Button } from '@/components/ui'
import { apiPost, tokenStore } from '@/lib/api'

export default function VerifyOtpPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [digits, setDigits] = useState(['', '', '', '', '', ''])
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const refs = useRef<(HTMLInputElement | null)[]>([])

  const [ctx, setCtx] = useState<{ destination?: string; debug?: string }>({})
  useEffect(() => {
    try {
      setCtx(JSON.parse(sessionStorage.getItem('bk.otp') ?? '{}'))
    } catch {
      /* ignore */
    }
  }, [])

  function set(i: number, v: string) {
    if (!/^\d?$/.test(v)) return
    const next = [...digits]
    next[i] = v
    setDigits(next)
    if (v && i < 5) refs.current[i + 1]?.focus()
  }

  async function verify() {
    setBusy(true)
    setError(null)
    try {
      const res = await apiPost('/auth/verify-otp', {
        destination: ctx.destination,
        code: digits.join(''),
        purpose: 'login',
      })
      tokenStore.set(res.access_token, res.refresh_token)
      sessionStorage.removeItem('bk.otp')
      window.location.href = '/'
    } catch (e: any) {
      setError(e?.message ?? t('common.error'))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthLayout>
      <h1 className="font-display text-2xl font-bold text-neutral-900">{t('auth.otpTitle')}</h1>
      <p className="mt-1.5 text-sm text-neutral-500">
        {t('auth.otpSubtitle', { target: ctx.destination ?? '' })}
      </p>
      {ctx.debug && (
        <p className="mt-2 rounded-lg bg-amber-50 px-3 py-1.5 text-[12px] text-amber-700">
          Dev code: <span className="font-mono font-semibold">{ctx.debug}</span>
        </p>
      )}

      <div className="mt-6 flex gap-2">
        {digits.map((d, i) => (
          <input
            key={i}
            ref={(el) => {
              refs.current[i] = el
            }}
            value={d}
            onChange={(e) => set(i, e.target.value)}
            onKeyDown={(e) => e.key === 'Backspace' && !d && i > 0 && refs.current[i - 1]?.focus()}
            inputMode="numeric"
            maxLength={1}
            className="h-14 w-full rounded-xl border border-neutral-300 text-center font-display text-xl font-bold text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-100"
          />
        ))}
      </div>

      {error && <p className="mt-2 text-sm text-danger">{error}</p>}

      <Button className="mt-6 w-full" size="lg" loading={busy} onClick={verify}>
        {t('auth.otpVerify')}
      </Button>

      <button
        onClick={() => navigate('/login')}
        className="mt-4 w-full text-center text-[13px] font-medium text-primary-700 hover:underline"
      >
        {t('auth.backToSignIn')}
      </button>
    </AuthLayout>
  )
}
