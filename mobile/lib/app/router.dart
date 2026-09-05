import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'session.dart';

import '../screens/auth/login_screen.dart';
import '../screens/auth/otp_screen.dart';
import '../screens/auth/forgot_screen.dart';

import '../screens/admin/dashboard_screen.dart';
import '../screens/admin/members_screen.dart';
import '../screens/admin/member_form_screen.dart';
import '../screens/admin/member_detail_screen.dart';
import '../screens/admin/shares_screen.dart';
import '../screens/admin/savings_screen.dart';
import '../screens/admin/savings_account_screen.dart';
import '../screens/admin/loans_screen.dart';
import '../screens/admin/loan_detail_screen.dart';
import '../screens/admin/loan_products_screen.dart';
import '../screens/admin/guarantors_screen.dart';
import '../screens/admin/projects_screen.dart';
import '../screens/admin/project_detail_screen.dart';
import '../screens/admin/insurance_screen.dart';
import '../screens/admin/payments_screen.dart';
import '../screens/admin/accounting_screen.dart';
import '../screens/admin/journal_entry_screen.dart';
import '../screens/admin/reports_screen.dart';
import '../screens/admin/profit_distribution_screen.dart';
import '../screens/admin/notifications_screen.dart';
import '../screens/admin/users_screen.dart';
import '../screens/admin/roles_screen.dart';
import '../screens/admin/settings_screen.dart';
import '../screens/admin/audit_logs_screen.dart';

import '../screens/member/home_screen.dart';
import '../screens/member/m_shares_screen.dart';
import '../screens/member/m_savings_screen.dart';
import '../screens/member/m_loans_screen.dart';
import '../screens/member/m_loan_apply_screen.dart';
import '../screens/member/m_loan_detail_screen.dart';
import '../screens/member/m_repayments_screen.dart';
import '../screens/member/m_projects_screen.dart';
import '../screens/member/m_project_detail_screen.dart';
import '../screens/member/m_insurance_screen.dart';
import '../screens/member/m_transactions_screen.dart';
import '../screens/member/m_statements_screen.dart';
import '../screens/member/m_notifications_screen.dart';
import '../screens/member/m_profile_screen.dart';

CustomTransitionPage _page(Widget child) => CustomTransitionPage(
      child: child,
      transitionDuration: const Duration(milliseconds: 240),
      transitionsBuilder: (_, anim, __, child) {
        final curved = CurvedAnimation(parent: anim, curve: Curves.easeOutCubic);
        return FadeTransition(
          opacity: curved,
          child: SlideTransition(
            position: Tween(begin: const Offset(0, 0.03), end: Offset.zero).animate(curved),
            child: child,
          ),
        );
      },
    );

GoRouter buildRouter(Session session) {
  GoRoute r(String path, Widget Function(GoRouterState) b) =>
      GoRoute(path: path, pageBuilder: (_, s) => _page(b(s)));

  return GoRouter(
    initialLocation: '/',
    refreshListenable: session,
    redirect: (context, state) {
      final authed = session.isAuthed;
      final loc = state.matchedLocation;
      final onAuth = loc == '/login' || loc == '/verify-otp' || loc == '/forgot-password';
      if (!authed) return onAuth ? null : '/login';
      if (loc == '/' || onAuth) return session.isStaff ? '/admin' : '/member';
      if (loc.startsWith('/admin') && !session.isStaff) return '/member';
      if (loc.startsWith('/member') && session.isStaff) return '/admin';
      return null;
    },
    routes: [
      GoRoute(path: '/', redirect: (_, __) => '/login'),
      r('/login', (_) => const LoginScreen()),
      r('/verify-otp', (_) => const OtpScreen()),
      r('/forgot-password', (_) => const ForgotScreen()),

      r('/admin', (_) => const DashboardScreen()),
      r('/admin/members', (_) => const MembersScreen()),
      r('/admin/members/new', (_) => const MemberFormScreen()),
      r('/admin/members/:id', (s) => MemberDetailScreen(id: s.pathParameters['id']!)),
      r('/admin/members/:id/edit', (s) => MemberFormScreen(id: s.pathParameters['id'])),
      r('/admin/shares', (_) => const SharesScreen()),
      r('/admin/savings', (_) => const SavingsScreen()),
      r('/admin/savings/:id', (s) => SavingsAccountScreen(id: s.pathParameters['id']!)),
      r('/admin/loans', (_) => const LoansScreen()),
      r('/admin/loans/:id', (s) => LoanDetailScreen(id: s.pathParameters['id']!)),
      r('/admin/loan-products', (_) => const LoanProductsScreen()),
      r('/admin/guarantors', (_) => const GuarantorsScreen()),
      r('/admin/projects', (_) => const ProjectsScreen()),
      r('/admin/projects/:id', (s) => ProjectDetailScreen(id: s.pathParameters['id']!)),
      r('/admin/insurance', (_) => const InsuranceScreen()),
      r('/admin/payments', (_) => const PaymentsScreen()),
      r('/admin/accounting', (_) => const AccountingScreen()),
      r('/admin/accounting/journal/:id', (s) => JournalEntryScreen(id: s.pathParameters['id']!)),
      r('/admin/reports', (_) => const ReportsScreen()),
      r('/admin/profit-distribution', (_) => const ProfitDistributionScreen()),
      r('/admin/notifications', (_) => const NotificationsScreen()),
      r('/admin/users', (_) => const UsersScreen()),
      r('/admin/roles', (_) => const RolesScreen()),
      r('/admin/settings', (_) => const SettingsScreen()),
      r('/admin/audit-logs', (_) => const AuditLogsScreen()),

      r('/member', (_) => const HomeScreen()),
      r('/member/shares', (_) => const MSharesScreen()),
      r('/member/savings', (_) => const MSavingsScreen()),
      r('/member/loans', (_) => const MLoansScreen()),
      r('/member/loans/apply', (_) => const MLoanApplyScreen()),
      r('/member/loans/:id', (s) => MLoanDetailScreen(id: s.pathParameters['id']!)),
      r('/member/repayments', (_) => const MRepaymentsScreen()),
      r('/member/projects', (_) => const MProjectsScreen()),
      r('/member/projects/:id', (s) => MProjectDetailScreen(id: s.pathParameters['id']!)),
      r('/member/insurance', (_) => const MInsuranceScreen()),
      r('/member/transactions', (_) => const MTransactionsScreen()),
      r('/member/statements', (_) => const MStatementsScreen()),
      r('/member/notifications', (_) => const MNotificationsScreen()),
      r('/member/profile', (_) => const MProfileScreen()),
    ],
  );
}
