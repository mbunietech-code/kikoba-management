import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

const _workflow = ['submitted', 'under_review', 'approved', 'disbursed', 'active', 'completed'];

class LoanDetailScreen extends StatelessWidget {
  const LoanDetailScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final loan = mock.loans.where((l) => l.id == id).firstOrNull;
    if (loan == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final schedule = mock.schedules.where((r) => r.loanId == id).toList();
    final repayments = mock.repayments.where((r) => r.loanId == id).toList();
    final step = _workflow.indexOf(loan.status);
    final canDecide = ['submitted', 'under_review'].contains(loan.status);

    return AppScaffold(
      title: loan.loanNumber,
      subtitle: '${loan.productName} · ${mock.memberName(loan.memberId)}',
      showBackButton: true,
      body: DefaultTabController(
        length: 3,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: KCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  KCardHeader(title: t('loans.workflow')),
                  const SizedBox(height: 12),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(children: [
                      for (var i = 0; i < _workflow.length; i++) ...[
                        Column(children: [
                          Container(
                            width: 30, height: 30,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: i <= step && step >= 0 ? K.primary600 : Colors.white,
                              shape: BoxShape.circle,
                              border: Border.all(color: i <= step && step >= 0 ? K.primary600 : K.neutral300, width: 2),
                            ),
                            child: i <= step && step >= 0
                                ? const Icon(Icons.check, size: 15, color: Colors.white)
                                : Text('${i + 1}', style: const TextStyle(fontSize: 11, color: K.neutral400, fontWeight: FontWeight.w700)),
                          ),
                          const SizedBox(height: 4),
                          SizedBox(width: 62, child: Text(t('loans.status.${_workflow[i]}'), textAlign: TextAlign.center, style: const TextStyle(fontSize: 9.5, color: K.neutral500))),
                        ]),
                        if (i < _workflow.length - 1)
                          Container(width: 20, height: 2, margin: const EdgeInsets.only(bottom: 22), color: i < step ? K.primary500 : K.neutral200),
                      ],
                    ]),
                  ),
                ]),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
              child: Row(children: [
                Expanded(child: StatCard(label: t('loans.outstanding'), value: money(loan.outstanding, compact: true), tone: Tone.neutral)),
                const SizedBox(width: 10),
                Expanded(child: StatCard(label: t('loans.totalRepayable'), value: money(loan.total, compact: true))),
              ]),
            ),
            if (canDecide || loan.status == 'approved' || ['active', 'overdue', 'disbursed'].contains(loan.status))
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                child: Row(children: [
                  if (canDecide) ...[
                    Expanded(child: OutlinedButton(onPressed: () => toast(context, '${t('common.reject')} ✓'), style: OutlinedButton.styleFrom(foregroundColor: K.danger), child: Text(t('common.reject')))),
                    const SizedBox(width: 10),
                    Expanded(child: FilledButton(onPressed: () => toast(context, '${t('common.approve')} ✓'), child: Text(t('common.approve')))),
                  ] else if (loan.status == 'approved')
                    Expanded(child: FilledButton(onPressed: () => toast(context, '${t('loans.disburse')} ✓'), child: Text(t('loans.disburse'))))
                  else
                    Expanded(child: FilledButton(onPressed: () => toast(context, '${t('loans.recordRepayment')} ✓'), child: Text(t('loans.recordRepayment')))),
                ]),
              ),
            TabBar(
              labelColor: K.primary700,
              unselectedLabelColor: K.neutral500,
              indicatorColor: K.primary600,
              tabs: [
                Tab(text: t('loans.tabs.overview')),
                Tab(text: '${t('loans.tabs.schedule')} (${schedule.length})'),
                Tab(text: '${t('loans.tabs.repayments')} (${repayments.length})'),
              ],
            ),
            Expanded(
              child: TabBarView(children: [
                ListView(padding: const EdgeInsets.all(16), children: [
                  KCard(
                    child: InfoGrid([
                      (t('common.member'), MemberInline(loan.memberId, dense: true)),
                      (t('loans.product'), Text(loan.productName)),
                      (t('loans.purpose'), Text(loan.purpose)),
                      (t('loans.period'), Text('${loan.period} ${t('loans.months')}')),
                      (t('loans.principal'), Text(money(loan.principal))),
                      (t('loans.interest'), Text(money(loan.interest))),
                      (t('loans.fees'), Text(money(loan.fees + loan.insurance))),
                      (t('loans.applicationDate'), Text(fmtDate(loan.applicationDate))),
                      (t('loans.disbursementDate'), Text(loan.disbursementDate == null ? '—' : fmtDate(loan.disbursementDate!))),
                      (t('loans.maturityDate'), Text(loan.maturityDate == null ? '—' : fmtDate(loan.maturityDate!))),
                    ]),
                  ),
                ]),
                ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: schedule.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) {
                    final r = schedule[i];
                    return KCard(
                      padding: const EdgeInsets.all(12),
                      child: Row(children: [
                        CircleAvatar(radius: 13, backgroundColor: K.neutral100, child: Text('${r.installment}', style: const TextStyle(fontSize: 11, color: K.neutral600))),
                        const SizedBox(width: 12),
                        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(fmtDate(r.dueDate), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                          Text('${money(r.amountPaid, compact: true)} / ${money(r.totalDue, compact: true)}', style: const TextStyle(fontSize: 11, color: K.neutral400)),
                        ])),
                        StatusBadge(r.status, label: t('loans.scheduleStatus.${r.status}')),
                      ]),
                    );
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
                          return KCard(
                            padding: const EdgeInsets.all(12),
                            child: Row(children: [
                              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text(fmtDate(r.date), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                                Text(r.reference, style: const TextStyle(fontSize: 11, color: K.neutral400)),
                              ])),
                              Text(money(r.totalPaid), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                              const SizedBox(width: 8),
                              KBadge(t('payments.methods.${r.method}'), tone: Tone.info),
                            ]),
                          );
                        },
                      ),
              ]),
            ),
          ],
        ),
      ),
    );
  }
}
