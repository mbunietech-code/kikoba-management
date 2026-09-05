import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/charts.dart';
import '../../widgets/ui.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final s = groupSummary();
    final name = context.watch<Session>().name.split(' ').first;

    final pending = mock.loans.where((l) => ['submitted', 'under_review'].contains(l.status)).toList();
    final upcoming = (mock.schedules.where((r) => r.status == 'pending' || r.status == 'overdue').toList()
          ..sort((a, b) => a.dueDate.compareTo(b.dueDate)))
        .take(4)
        .toList();

    return AppScaffold(
      title: t('dashboard.welcome', {'name': name}),
      subtitle: t('dashboard.overview'),
      currentRoute: '/admin',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          _grid([
            StatCard(label: t('dashboard.totalMembers'), value: '${s.totalMembers}', icon: Icons.groups_rounded, delta: '+${s.newMembers}'),
            StatCard(label: t('dashboard.totalSavings'), value: money(s.totalSavings, compact: true), icon: Icons.savings_rounded, tone: Tone.info, delta: '6.4%'),
            StatCard(label: t('dashboard.totalShares'), value: money(s.totalShares, compact: true), icon: Icons.pie_chart_rounded, tone: Tone.success, delta: '2.1%'),
            StatCard(label: t('dashboard.outstandingLoans'), value: money(s.outstanding, compact: true), icon: Icons.request_quote_rounded, tone: Tone.neutral, hint: '${t('dashboard.portfolioAtRisk')} ${pct(s.par * 100)}'),
            StatCard(label: t('dashboard.totalLoans'), value: money(s.disbursed, compact: true), icon: Icons.payments_rounded),
            StatCard(label: t('dashboard.totalProfit'), value: money(s.netProfit, compact: true), icon: Icons.trending_up_rounded, tone: Tone.info, delta: '12%'),
          ]),
          const SizedBox(height: 14),
          Reveal(
            delayMs: 60,
            child: KCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                KCardHeader(title: t('dashboard.cashFlow'), subtitle: 'Mar – Sep 2026'),
                const SizedBox(height: 12),
                AreaTrend(data: cashFlowSeries(), keys: const ['inflow', 'outflow'], colors: const [K.primary800, K.danger]),
              ]),
            ),
          ),
          const SizedBox(height: 14),
          Reveal(
            delayMs: 100,
            child: KCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                KCardHeader(title: t('dashboard.loanStatus')),
                const SizedBox(height: 8),
                DonutChart(entries: loanStatusBreakdown().entries.map((e) => MapEntry(t('loans.status.${e.key}'), e.value)).toList()),
              ]),
            ),
          ),
          const SizedBox(height: 14),
          Reveal(
            delayMs: 140,
            child: KCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                KCardHeader(title: t('dashboard.savingsVsLoans')),
                const SizedBox(height: 12),
                AreaTrend(data: savingsVsLoansSeries(), keys: const ['savings', 'loans'], colors: const [K.secondary600, K.tertiary600]),
              ]),
            ),
          ),
          const SizedBox(height: 14),
          Reveal(
            delayMs: 180,
            child: KCard(
              padding: EdgeInsets.zero,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
                  child: KCardHeader(
                    title: t('dashboard.pendingApprovals'),
                    trailing: TextButton(onPressed: () => context.go('/admin/loans'), child: Text(t('common.viewAll'))),
                  ),
                ),
                if (pending.isEmpty)
                  Padding(padding: const EdgeInsets.all(16), child: Text(t('common.noData'), style: const TextStyle(color: K.neutral400)))
                else
                  for (final l in pending) ...[
                    const ListDivider(),
                    ListTile(
                      onTap: () => context.go('/admin/loans/${l.id}'),
                      title: MemberInline(l.memberId, dense: true),
                      trailing: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(money(l.principal, compact: true), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                          Text(l.productName, style: const TextStyle(fontSize: 11, color: K.neutral400)),
                        ],
                      ),
                    ),
                  ],
              ]),
            ),
          ),
          const SizedBox(height: 14),
          Reveal(
            delayMs: 220,
            child: KCard(
              padding: EdgeInsets.zero,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
                  child: KCardHeader(title: t('dashboard.upcomingRepayments')),
                ),
                for (final r in upcoming) ...[
                  const ListDivider(),
                  Builder(builder: (_) {
                    final loan = mock.loans.firstWhere((l) => l.id == r.loanId);
                    return ListTile(
                      onTap: () => context.go('/admin/loans/${r.loanId}'),
                      title: MemberInline(loan.memberId, dense: true),
                      trailing: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(money(r.totalDue, compact: true), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                          Text(fmtDate(r.dueDate), style: const TextStyle(fontSize: 11, color: K.neutral400)),
                        ],
                      ),
                    );
                  }),
                ],
              ]),
            ),
          ),
        ],
      ),
    );
  }

  Widget _grid(List<Widget> cards) {
    return LayoutBuilder(builder: (context, c) {
      final cross = c.maxWidth > 520 ? 3 : 2;
      return GridView.count(
        crossAxisCount: cross,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 1.45,
        children: [for (var i = 0; i < cards.length; i++) Reveal(delayMs: i * 40, child: cards[i])],
      );
    });
  }
}
