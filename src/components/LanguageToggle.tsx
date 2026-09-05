import { useTranslation } from 'react-i18next'
import { Languages } from 'lucide-react'
import { LANGUAGES } from '@/i18n'
import { cn } from '@/lib/cn'

export function LanguageToggle({ compact = false }: { compact?: boolean }) {
  const { i18n } = useTranslation()
  const current = i18n.resolvedLanguage ?? 'en'

  if (compact) {
    const next = current === 'en' ? 'sw' : 'en'
    return (
      <button
        onClick={() => i18n.changeLanguage(next)}
        className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-[13px] font-medium text-neutral-600 transition-colors hover:bg-neutral-50"
        title="Switch language"
      >
        <Languages className="h-4 w-4" />
        {current === 'en' ? 'EN' : 'SW'}
      </button>
    )
  }

  return (
    <div className="inline-flex rounded-lg border border-neutral-200 bg-neutral-50 p-0.5">
      {LANGUAGES.map((lang) => (
        <button
          key={lang.code}
          onClick={() => i18n.changeLanguage(lang.code)}
          className={cn(
            'rounded-md px-2.5 py-1 text-[13px] font-medium transition-colors',
            current === lang.code ? 'bg-white text-primary-700 shadow-sm' : 'text-neutral-500 hover:text-neutral-700',
          )}
        >
          {lang.short}
        </button>
      ))}
    </div>
  )
}
