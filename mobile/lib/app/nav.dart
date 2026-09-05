import 'package:flutter/material.dart';

class NavDest {
  final String route;
  final String labelKey;
  final IconData icon;
  const NavDest(this.route, this.labelKey, this.icon);
}

// Bottom-bar primary destinations
const adminTabs = <NavDest>[
  NavDest('/admin', 'nav.dashboard', Icons.grid_view_rounded),
  NavDest('/admin/members', 'nav.members', Icons.groups_rounded),
  NavDest('/admin/loans', 'nav.loans', Icons.request_quote_rounded),
  NavDest('/admin/savings', 'nav.savings', Icons.savings_rounded),
];

const memberTabs = <NavDest>[
  NavDest('/member', 'nav.home', Icons.home_rounded),
  NavDest('/member/savings', 'nav.mySavings', Icons.savings_rounded),
  NavDest('/member/loans', 'nav.myLoans', Icons.request_quote_rounded),
  NavDest('/member/projects', 'nav.myProjects', Icons.workspaces_rounded),
];

// "More" menu groups
const adminMore = <(String, List<NavDest>)>[
  ('nav.groupsMembers', [
    NavDest('/admin/shares', 'nav.shares', Icons.pie_chart_rounded),
    NavDest('/admin/insurance', 'nav.insurance', Icons.verified_user_rounded),
  ]),
  ('nav.groupsFinance', [
    NavDest('/admin/loan-products', 'nav.loanProducts', Icons.inventory_2_rounded),
    NavDest('/admin/guarantors', 'nav.guarantors', Icons.handshake_rounded),
    NavDest('/admin/projects', 'nav.projects', Icons.workspaces_rounded),
    NavDest('/admin/payments', 'nav.payments', Icons.credit_card_rounded),
    NavDest('/admin/accounting', 'nav.accounting', Icons.account_balance_rounded),
    NavDest('/admin/profit-distribution', 'nav.profitDistribution', Icons.trending_up_rounded),
  ]),
  ('nav.groupsAdmin', [
    NavDest('/admin/reports', 'nav.reports', Icons.bar_chart_rounded),
    NavDest('/admin/notifications', 'nav.notifications', Icons.notifications_rounded),
    NavDest('/admin/users', 'nav.users', Icons.manage_accounts_rounded),
    NavDest('/admin/roles', 'nav.roles', Icons.admin_panel_settings_rounded),
    NavDest('/admin/settings', 'nav.settings', Icons.tune_rounded),
    NavDest('/admin/audit-logs', 'nav.auditLogs', Icons.history_rounded),
  ]),
];

const memberMore = <(String, List<NavDest>)>[
  ('nav.groupsMain', [
    NavDest('/member/shares', 'nav.myShares', Icons.pie_chart_rounded),
    NavDest('/member/repayments', 'nav.repayments', Icons.payments_rounded),
    NavDest('/member/insurance', 'nav.myInsurance', Icons.verified_user_rounded),
  ]),
  ('nav.groupsAdmin', [
    NavDest('/member/transactions', 'nav.transactions', Icons.receipt_long_rounded),
    NavDest('/member/statements', 'nav.statements', Icons.description_rounded),
    NavDest('/member/notifications', 'nav.notifications', Icons.notifications_rounded),
    NavDest('/member/profile', 'common.profile', Icons.person_rounded),
  ]),
];
