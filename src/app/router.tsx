import { createBrowserRouter } from 'react-router-dom'
import { AppShell } from '@/components/layout/AppShell'
import { RequireAuth, RoleHome } from './guards'

import LoginPage from '@/pages/auth/LoginPage'
import VerifyOtpPage from '@/pages/auth/VerifyOtpPage'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage'
import ResetPasswordPage from '@/pages/auth/ResetPasswordPage'
import NotFoundPage from '@/pages/NotFoundPage'

import AdminDashboard from '@/pages/admin/AdminDashboard'
import MembersPage from '@/pages/admin/MembersPage'
import MemberFormPage from '@/pages/admin/MemberFormPage'
import MemberDetailPage from '@/pages/admin/MemberDetailPage'
import SharesPage from '@/pages/admin/SharesPage'
import SavingsPage from '@/pages/admin/SavingsPage'
import SavingsAccountPage from '@/pages/admin/SavingsAccountPage'
import LoansPage from '@/pages/admin/LoansPage'
import LoanDetailPage from '@/pages/admin/LoanDetailPage'
import LoanProductsPage from '@/pages/admin/LoanProductsPage'
import GuarantorsPage from '@/pages/admin/GuarantorsPage'
import ProjectsPage from '@/pages/admin/ProjectsPage'
import ProjectDetailPage from '@/pages/admin/ProjectDetailPage'
import InsurancePage from '@/pages/admin/InsurancePage'
import PaymentsPage from '@/pages/admin/PaymentsPage'
import AccountingPage from '@/pages/admin/AccountingPage'
import JournalEntryPage from '@/pages/admin/JournalEntryPage'
import ReportsPage from '@/pages/admin/ReportsPage'
import ProfitDistributionPage from '@/pages/admin/ProfitDistributionPage'
import NotificationsAdminPage from '@/pages/admin/NotificationsAdminPage'
import UsersPage from '@/pages/admin/UsersPage'
import RolesPage from '@/pages/admin/RolesPage'
import SettingsPage from '@/pages/admin/SettingsPage'
import AuditLogsPage from '@/pages/admin/AuditLogsPage'

import MemberHome from '@/pages/member/MemberHome'
import MemberShares from '@/pages/member/MemberShares'
import MemberSavings from '@/pages/member/MemberSavings'
import MemberLoans from '@/pages/member/MemberLoans'
import MemberLoanApply from '@/pages/member/MemberLoanApply'
import MemberLoanDetail from '@/pages/member/MemberLoanDetail'
import MemberRepayments from '@/pages/member/MemberRepayments'
import MemberProjects from '@/pages/member/MemberProjects'
import MemberProjectDetail from '@/pages/member/MemberProjectDetail'
import MemberInsurance from '@/pages/member/MemberInsurance'
import MemberTransactions from '@/pages/member/MemberTransactions'
import MemberStatements from '@/pages/member/MemberStatements'
import MemberNotifications from '@/pages/member/MemberNotifications'
import MemberProfile from '@/pages/member/MemberProfile'

export const router = createBrowserRouter([
  { path: '/', element: <RoleHome /> },
  { path: '/login', element: <LoginPage /> },
  { path: '/verify-otp', element: <VerifyOtpPage /> },
  { path: '/forgot-password', element: <ForgotPasswordPage /> },
  { path: '/reset-password', element: <ResetPasswordPage /> },
  {
    element: (
      <RequireAuth staff>
        <AppShell />
      </RequireAuth>
    ),
    children: [
      { path: '/admin', element: <AdminDashboard /> },
      { path: '/admin/members', element: <MembersPage /> },
      { path: '/admin/members/new', element: <MemberFormPage /> },
      { path: '/admin/members/:id', element: <MemberDetailPage /> },
      { path: '/admin/members/:id/edit', element: <MemberFormPage /> },
      { path: '/admin/shares', element: <SharesPage /> },
      { path: '/admin/savings', element: <SavingsPage /> },
      { path: '/admin/savings/:accountId', element: <SavingsAccountPage /> },
      { path: '/admin/loans', element: <LoansPage /> },
      { path: '/admin/loans/:id', element: <LoanDetailPage /> },
      { path: '/admin/loan-products', element: <LoanProductsPage /> },
      { path: '/admin/guarantors', element: <GuarantorsPage /> },
      { path: '/admin/projects', element: <ProjectsPage /> },
      { path: '/admin/projects/:id', element: <ProjectDetailPage /> },
      { path: '/admin/insurance', element: <InsurancePage /> },
      { path: '/admin/payments', element: <PaymentsPage /> },
      { path: '/admin/accounting', element: <AccountingPage /> },
      { path: '/admin/accounting/journal/:id', element: <JournalEntryPage /> },
      { path: '/admin/reports', element: <ReportsPage /> },
      { path: '/admin/profit-distribution', element: <ProfitDistributionPage /> },
      { path: '/admin/notifications', element: <NotificationsAdminPage /> },
      { path: '/admin/users', element: <UsersPage /> },
      { path: '/admin/roles', element: <RolesPage /> },
      { path: '/admin/settings', element: <SettingsPage /> },
      { path: '/admin/audit-logs', element: <AuditLogsPage /> },
    ],
  },
  {
    element: (
      <RequireAuth>
        <AppShell />
      </RequireAuth>
    ),
    children: [
      { path: '/member', element: <MemberHome /> },
      { path: '/member/shares', element: <MemberShares /> },
      { path: '/member/savings', element: <MemberSavings /> },
      { path: '/member/loans', element: <MemberLoans /> },
      { path: '/member/loans/apply', element: <MemberLoanApply /> },
      { path: '/member/loans/:id', element: <MemberLoanDetail /> },
      { path: '/member/repayments', element: <MemberRepayments /> },
      { path: '/member/projects', element: <MemberProjects /> },
      { path: '/member/projects/:id', element: <MemberProjectDetail /> },
      { path: '/member/insurance', element: <MemberInsurance /> },
      { path: '/member/transactions', element: <MemberTransactions /> },
      { path: '/member/statements', element: <MemberStatements /> },
      { path: '/member/notifications', element: <MemberNotifications /> },
      { path: '/member/profile', element: <MemberProfile /> },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])
