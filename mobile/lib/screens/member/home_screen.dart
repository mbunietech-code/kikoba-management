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

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final session = context.watch<Session>();
    final memberId = session.memberId ?? mock.currentMemberId;
    final member = mock.memberById(memberId)!;
    final pos = memberPosition(memberId);
    final txns = memberTransactions(memberId).take(5).toList();

    final nextRepay = pos.activeLoan == null
        ? null
        : (mock.schedules
                .where((r) => r.loanId == pos.activeLoan!.id && (r.status == 'pending' || r.status == 'overdue'))
                .toList()
              ..sort((a, b) => a.dueDate.compareTo(b.dueDate)))
            .firstOrNull;

    final savingsSeries = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
        .asMap()
        .entries
        .map((e) => {'month': e.value, 'savings': (pos.savingsBalance * (0.6 + e.key * 0.08)).round()})
        .toList();

    return AppScaffold(
      title: t('member.greeting', {'name': member.fullName.split(' ').first}),
      subtitle: t('member.myPosition'),
      currentRoute: '/member',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.5,
            children: [
              Reveal(child: StatCard(label: t('member.shareValue'), value: money(pos.shareValue, compact: true), hint: '${pos.shareQty} ${t('shares.quantity').toLowerCase()}')),
              Reveal(delayMs: 40, child: StatCard(label: t('member.savingsBalance'), value: money(pos.savingsBalance, compact: true), tone: Tone.info)),
              Reveal(delayMs: 80, child: StatCard(label: t('member.outstanding'), value: money(pos.loanOutstanding, compact: true), tone: Tone.neutral)),
              Reveal(delayMs: 120, child: StatCard(label: t('member.myProfit'), value: money(pos.profit, compact: true), tone: Tone.success)),
            ],
          ),
          const SizedBox(height: 14),
          Reveal(
            delayMs: 140,
            child: KCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                KCardHeader(title: t('member.savingsBalance'), subtitle: 'Apr – Sep 2026'),
                const SizedBox(height: 12),
                AreaTrend(data: savingsSeries, keys: const ['savings'], colors: const [K.secondary600]),
              ]),
            ),
          ),
          if (nextRepay != null) ...[
            const SizedBox(height: 14),
            Reveal(
              delayMs: 160,
              child: KCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(t('member.nextRepayment'), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w500, color: K.neutral500)),
                  const SizedBox(height: 4),
                  Text(money(nextRepay.totalDue), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: K.neutral900)),
                  const SizedBox(height: 4),
                  Row(children: [
                    Text(fmtDate(nextRepay.dueDate), style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
                    const SizedBox(width: 8),
                    if (nextRepay.status == 'overdue') const KBadge('overdue', tone: Tone.danger, dot: true),
                  ]),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () => context.go('/member/loans/${pos.activeLoan!.id}'),
                      child: Text(t('loans.recordRepayment')),
                    ),
                  ),
                ]),
              ),
            ),
          ],
          const SizedBox(height: 14),
          Reveal(
            delayMs: 180,
            child: KCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                KCardHeader(title: t('member.quickActions')),
                const SizedBox(height: 12),
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  childAspectRatio: 3,
                  children: [
                    _action(context, t('member.applyLoan'), Icons.request_quote_rounded, '/member/loans/apply'),
                    _action(context, t('member.makeDeposit'), Icons.savings_rounded, '/member/savings'),
                    _action(context, t('member.buyShares'), Icons.add_circle_outline_rounded, '/member/shares'),
                    _action(context, t('member.downloadStatement'), Icons.download_rounded, '/member/statements'),
                  ],
                ),
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
                  child: KCardHeader(
                    title: '${t('common.recent')} — ${t('nav.transactions')}',
                    trailing: TextButton(onPressed: () => context.go('/member/transactions'), child: Text(t('common.viewAll'))),
                  ),
                ),
                for (final tx in txns) ...[
                  const ListDivider(),
                  ListTile(
                    leading: Container(
                      width: 36, height: 36,
                      decoration: BoxDecoration(color: K.neutral100, borderRadius: BorderRadius.circular(10)),
                      child: const Icon(Icons.paid_outlined, size: 18, color: K.neutral500),
                    ),
                    title: Text(t('transactions.types.${tx.type}'), style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600)),
                    subtitle: Text('${tx.reference} · ${fmtDate(tx.createdAt)}', style: const TextStyle(fontSize: 11.5)),
                    trailing: Text(money(tx.amount), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                  ),
                ],
              ]),
            ),
          ),
        ],
      ),
    );
  }

  Widget _action(BuildContext context, String label, IconData icon, String route) {
    return InkWell(
      onTap: () => context.go(route),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(border: Border.all(color: K.neutral200), borderRadius: BorderRadius.circular(12)),
        child: Row(children: [
          Icon(icon, size: 18, color: K.primary700),
          const SizedBox(width: 8),
          Expanded(child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: K.neutral700), maxLines: 2)),
        ]),
      ),
    );
  }
}
