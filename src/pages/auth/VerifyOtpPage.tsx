import { useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { AuthLayout } from './AuthLayout'
import { Button } from '@/components/ui'
import { useSession } from '@/app/session'

export default function VerifyOtpPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { signIn } = useSession()
  const [digits, setDigits] = useState(['', '', '', '', '', ''])
  const refs = useRef<(HTMLInputElement | null)[]>([])

  function set(i: number, v: string) {
    if (!/^\d?$/.test(v)) return
    const next = [...digits]
    next[i] = v
    setDigits(next)
    if (v && i < 5) refs.current[i + 1]?.focus()
  }

  return (
    <AuthLayout>
      <h1 className="font-display text-2xl font-bold text-neutral-900">{t('auth.otpTitle')}</h1>
      <p className="mt-1.5 text-sm text-neutral-500">{t('auth.otpSubtitle', { target: '+255 7•• ••• 218' })}</p>

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

      <Button
        className="mt-6 w-full"
        size="lg"
        onClick={() => {
          signIn('member')
          navigate('/member')
        }}
      >
        {t('auth.otpVerify')}
      </Button>

      <button className="mt-4 w-full text-center text-[13px] font-medium text-primary-700 hover:underline">
        {t('auth.otpResend')}
      </button>
    </AuthLayout>
  )
}
