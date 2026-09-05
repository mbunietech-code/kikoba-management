import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { SearchInput, Select } from '@/components/ui'

export function ListToolbar({
  search,
  onSearch,
  filters,
  right,
}: {
  search: string
  onSearch: (v: string) => void
  filters?: { value: string; onChange: (v: string) => void; options: { value: string; label: string }[] }[]
  right?: ReactNode
}) {
  const { t } = useTranslation()
  return (
    <div className="mb-4 flex flex-wrap items-center gap-2">
      <SearchInput
        value={search}
        onChange={(e) => onSearch(e.target.value)}
        placeholder={t('common.searchPlaceholder')}
        className="w-full sm:w-64"
      />
      {filters?.map((f, i) => (
        <Select key={i} value={f.value} onChange={(e) => f.onChange(e.target.value)} className="w-auto min-w-36">
          {f.options.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </Select>
      ))}
      {right && <div className="ml-auto flex items-center gap-2">{right}</div>}
    </div>
  )
}
