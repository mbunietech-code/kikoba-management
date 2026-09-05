import { useSession } from '@/app/session'
import { CURRENT_MEMBER_ID, memberById } from '@/mock/data'

export function useMemberId() {
  const { session } = useSession()
  return session?.memberId ?? CURRENT_MEMBER_ID
}

export function useCurrentMember() {
  const id = useMemberId()
  return memberById(id)!
}
