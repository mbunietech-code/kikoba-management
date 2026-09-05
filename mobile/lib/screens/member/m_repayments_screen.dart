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

class MRepaymentsScreen extends StatelessWidget {
  const MRepaymentsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = currentMemberId(context);
    final myLoanIds = mock.loans.where((l) => l.memberId == id).map((l) => l.id).toSet();
    final history = mock.repayments.where((r) => myLoanIds.contains(r.loanId)).toList()..sort((a, b) => b.date.compareTo(a.date));
    final upcoming = mock.schedules
        .where((r) => myLoanIds.contains(r.loanId) && (r.status == 'pending' || r.status == 'overdue' || r.status == 'partial'))
        .toList()
      ..sort((a, b) => a.dueDate.compareTo(b.dueDate));
    final totalPaid = sumI(history.map((r) => r.totalPaid));
    final dueSoon = sumI(upcoming.map((r) => r.totalDue - r.amountPaid));
    String loanNo(String lid) => mock.loans.firstWhere((l) => l.id == lid).loanNumber;

    return AppScaffold(
      title: t('nav.repayments'),
      subtitle: t('loans.repaymentHistory'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('loans.repaymentHistory'), value: money(totalPaid, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('dashboard.upcomingRepayments'), value: money(dueSoon, compact: true), tone: Tone.neutral)),
          ]),
          const SizedBox(height: 14),
          SectionTitle(t('dashboard.upcomingRepayments')),
          if (upcoming.isEmpty) KCard(child: EmptyState(title: t('common.noData'))),
          for (final r in upcoming)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(
                onTap: () => context.go('/member/loans/${r.loanId}'),
                padding: const EdgeInsets.all(12),
                child: Row(children: [
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('${loanNo(r.loanId)} · #${r.installment}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    Text(fmtDate(r.dueDate), style: const TextStyle(fontSize: 11, color: K.neutral400)),
                  ])),
                  Text(money(r.totalDue - r.amountPaid), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                  const SizedBox(width: 8),
                  KBadge(t('loans.scheduleStatus.${r.status}'), tone: r.status == 'overdue' ? Tone.danger : Tone.warning, dot: true),
                ]),
              ),
            ),
          const SizedBox(height: 8),
          SectionTitle(t('loans.repaymentHistory')),
          for (final r in history)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('${fmtDate(r.date)} · ${loanNo(r.loanId)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                  Text(r.reference, style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                ])),
                Text(money(r.totalPaid), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
              ])),
            ),
        ],
      ),
    );
  }
}
