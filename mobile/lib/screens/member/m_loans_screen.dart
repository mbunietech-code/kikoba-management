import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MLoansScreen extends StatelessWidget {
  const MLoansScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = currentMemberId(context);
    final rows = mock.loans.where((l) => l.memberId == id).toList();
    final active = rows.where((l) => ['active', 'overdue', 'disbursed'].contains(l.status)).length;
    final outstanding = sumI(rows.map((l) => l.outstanding));

    return AppScaffold(
      title: t('nav.myLoans'),
      subtitle: t('loans.subtitle'),
      currentRoute: '/member/loans',
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => context.go('/member/loans/apply'),
        icon: const Icon(Icons.add), label: Text(t('member.applyLoan')),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('nav.active'), value: '$active')),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('member.outstanding'), value: money(outstanding, compact: true), tone: Tone.neutral)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('common.total'), value: '${rows.length}', tone: Tone.info)),
          ]),
          const SizedBox(height: 14),
          if (rows.isEmpty)
            KCard(child: EmptyState(
              title: t('common.noData'),
              hint: t('loans.subtitle'),
              action: FilledButton(onPressed: () => context.go('/member/loans/apply'), child: Text(t('member.applyLoan'))),
            )),
          for (final l in rows)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(
                onTap: () => context.go('/member/loans/${l.id}'),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Text(l.loanNumber, style: const TextStyle(fontWeight: FontWeight.w700, color: K.primary700)),
                    const Spacer(),
                    StatusBadge(l.status, label: t('loans.status.${l.status}')),
                  ]),
                  Text(l.productName, style: const TextStyle(fontSize: 12, color: K.neutral500)),
                  const SizedBox(height: 10),
                  Row(children: [
                    Expanded(child: _kv(t('loans.principal'), money(l.principal, compact: true))),
                    Expanded(child: _kv(t('loans.outstanding'), money(l.outstanding, compact: true))),
                  ]),
                  const SizedBox(height: 8),
                  KProgress(l.total == 0 ? 0 : l.amountPaid / l.total * 100, showLabel: true, tone: l.status == 'overdue' ? Tone.danger : Tone.primary),
                ]),
              ),
            ),
        ],
      ),
    );
  }

  Widget _kv(String k, String v) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(k, style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
        Text(v, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
      ]);
}
