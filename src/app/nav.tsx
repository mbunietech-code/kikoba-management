import type { ComponentType } from 'react'
import {
  LayoutDashboard, Users, PieChart, PiggyBank, Landmark, HandCoins, ShieldCheck,
  FolderKanban, CreditCard, BookOpenText, FileBarChart, Bell, UserCog, SlidersHorizontal,
  ScrollText, Coins, Wallet, ReceiptText, FileText, Home, TrendingUp, Package,
} from 'lucide-react'
import type { Role } from '@/types'

export interface NavItem {
  to: string
  labelKey: string
  icon: ComponentType<{ className?: string }>
  end?: boolean
  roles?: Role[]
}
export interface NavSection {
  titleKey: string
  items: NavItem[]
}

export const adminNav: NavSection[] = [
  {
    titleKey: 'nav.groupsMain',
    items: [
      { to: '/admin', labelKey: 'nav.dashboard', icon: LayoutDashboard, end: true },
    ],
  },
  {
    titleKey: 'nav.groupsMembers',
    items: [
      { to: '/admin/members', labelKey: 'nav.members', icon: Users },
      { to: '/admin/shares', labelKey: 'nav.shares', icon: PieChart },
      { to: '/admin/insurance', labelKey: 'nav.insurance', icon: ShieldCheck },
    ],
  },
  {
    titleKey: 'nav.groupsFinance',
    items: [
      { to: '/admin/savings', labelKey: 'nav.savings', icon: PiggyBank },
      { to: '/admin/loans', labelKey: 'nav.loans', icon: HandCoins },
      { to: '/admin/loan-products', labelKey: 'nav.loanProducts', icon: Package },
      { to: '/admin/guarantors', labelKey: 'nav.guarantors', icon: ShieldCheck },
      { to: '/admin/projects', labelKey: 'nav.projects', icon: FolderKanban },
      { to: '/admin/payments', labelKey: 'nav.payments', icon: CreditCard },
      { to: '/admin/accounting', labelKey: 'nav.accounting', icon: BookOpenText },
      { to: '/admin/profit-distribution', labelKey: 'nav.profitDistribution', icon: TrendingUp },
    ],
  },
  {
    titleKey: 'nav.groupsAdmin',
    items: [
      { to: '/admin/reports', labelKey: 'nav.reports', icon: FileBarChart },
      { to: '/admin/notifications', labelKey: 'nav.notifications', icon: Bell },
      { to: '/admin/users', labelKey: 'nav.users', icon: UserCog, roles: ['super_admin', 'admin'] },
      { to: '/admin/roles', labelKey: 'nav.roles', icon: Landmark, roles: ['super_admin', 'admin'] },
      { to: '/admin/settings', labelKey: 'nav.settings', icon: SlidersHorizontal, roles: ['super_admin', 'admin'] },
      { to: '/admin/audit-logs', labelKey: 'nav.auditLogs', icon: ScrollText, roles: ['super_admin', 'admin'] },
    ],
  },
]

export const memberNav: NavSection[] = [
  {
    titleKey: 'nav.groupsMain',
    items: [
      { to: '/member', labelKey: 'nav.home', icon: Home, end: true },
      { to: '/member/shares', labelKey: 'nav.myShares', icon: PieChart },
      { to: '/member/savings', labelKey: 'nav.mySavings', icon: PiggyBank },
    ],
  },
  {
    titleKey: 'nav.groupsFinance',
    items: [
      { to: '/member/loans', labelKey: 'nav.myLoans', icon: HandCoins },
      { to: '/member/repayments', labelKey: 'nav.repayments', icon: Coins },
      { to: '/member/projects', labelKey: 'nav.myProjects', icon: FolderKanban },
      { to: '/member/insurance', labelKey: 'nav.myInsurance', icon: ShieldCheck },
    ],
  },
  {
    titleKey: 'nav.groupsAdmin',
    items: [
      { to: '/member/transactions', labelKey: 'nav.transactions', icon: ReceiptText },
      { to: '/member/statements', labelKey: 'nav.statements', icon: FileText },
      { to: '/member/notifications', labelKey: 'nav.notifications', icon: Bell },
    ],
  },
]

export const walletIcon = Wallet
