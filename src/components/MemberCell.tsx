import { Link } from 'react-router-dom'
import { Avatar } from '@/components/ui'
import { memberById } from '@/mock/data'

export function MemberCell({ memberId, link = true }: { memberId: string; link?: boolean }) {
  const m = memberById(memberId)
  if (!m) return <span className="text-neutral-400">—</span>
  const inner = (
    <span className="flex items-center gap-2.5">
      <Avatar name={m.fullName} color={m.avatarColor} size="sm" />
      <span className="min-w-0">
        <span className="block truncate text-[13.5px] font-medium text-neutral-800">{m.fullName}</span>
        <span className="block text-[11px] text-neutral-400">{m.memberNumber}</span>
      </span>
    </span>
  )
  return link ? (
    <Link to={`/admin/members/${m.id}`} className="hover:opacity-80" onClick={(e) => e.stopPropagation()}>
      {inner}
    </Link>
  ) : (
    inner
  )
}
