import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MLoanDetailScreen extends StatelessWidget {
  const MLoanDetailScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final mid = currentMemberId(context);
    final loan = mock.loans.where((l) => l.id == id && l.memberId == mid).firstOrNull;
    if (loan == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final schedule = mock.schedules.where((r) => r.loanId == id).toList();
    final repayments = mock.repayments.where((r) => r.loanId == id).toList();

    return AppScaffold(
      title: loan.loanNumber,
      subtitle: loan.productName,
      showBackButton: true,
      body: DefaultTabController(
        length: 2,
        child: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Column(children: [
              Row(children: [
                Expanded(child: StatCard(label: t('member.outstanding'), value: money(loan.outstanding, compact: true), tone: Tone.neutral)),
                const SizedBox(width: 10),
                Expanded(child: StatCard(label: t('loans.totalRepayable'), value: money(loan.total, compact: true))),
              ]),
              const SizedBox(height: 10),
              KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                KProgress(loan.total == 0 ? 0 : loan.amountPaid / loan.total * 100, showLabel: true, tone: loan.status == 'overdue' ? Tone.danger : Tone.success),
                const SizedBox(height: 6),
                Text('${money(loan.amountPaid)} / ${money(loan.total)}', style: const TextStyle(fontSize: 11.5, color: K.neutral500)),
              ])),
            ]),
          ),
          if (['active', 'overdue', 'disbursed'].contains(loan.status))
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: SizedBox(
                width: double.infinity,
                child: FilledButton(onPressed: () => toast(context, '${t('loans.recordRepayment')} ✓'), child: Text(t('loans.recordRepayment'))),
              ),
            ),
          TabBar(
            labelColor: K.primary700, unselectedLabelColor: K.neutral500, indicatorColor: K.primary600,
            tabs: [Tab(text: '${t('loans.tabs.schedule')} (${schedule.length})'), Tab(text: '${t('loans.tabs.repayments')} (${repayments.length})')],
          ),
          Expanded(child: TabBarView(children: [
            ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: schedule.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (_, i) {
                final r = schedule[i];
                return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                  CircleAvatar(radius: 13, backgroundColor: K.neutral100, child: Text('${r.installment}', style: const TextStyle(fontSize: 11, color: K.neutral600))),
                  const SizedBox(width: 12),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(fmtDate(r.dueDate), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                    Text(money(r.totalDue), style: const TextStyle(fontSize: 11, color: K.neutral400)),
                  ])),
                  StatusBadge(r.status, label: t('loans.scheduleStatus.${r.status}')),
                ]));
              },
            ),
            repayments.isEmpty
                ? EmptyState(title: t('common.noData'))
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: repayments.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (_, i) {
                      final r = repayments[i];
                      return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(fmtDate(r.date), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                          Text(r.reference, style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                        ])),
                        Text(money(r.totalPaid), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                        const SizedBox(width: 8),
                        KBadge(t('payments.methods.${r.method}'), tone: Tone.info),
                      ]));
                    },
                  ),
          ])),
        ]),
      ),
    );
  }
}
