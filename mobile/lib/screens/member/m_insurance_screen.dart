import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MInsuranceScreen extends StatelessWidget {
  const MInsuranceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = currentMemberId(context);
    final acc = mock.insuranceAccounts.where((a) => a.memberId == id).firstOrNull;
    final contributions = mock.insuranceContributions.where((c) => c.memberId == id).toList()..sort((a, b) => b.date.compareTo(a.date));
    final claims = mock.insuranceClaims.where((c) => c.memberId == id).toList();

    return AppScaffold(
      title: t('nav.myInsurance'),
      subtitle: t('insurance.subtitle'),
      showBackButton: true,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => toast(context, '${t('insurance.fileClaim')} ✓'),
        icon: const Icon(Icons.note_add_outlined), label: Text(t('insurance.fileClaim')),
      ),
      body: acc == null
          ? EmptyState(title: t('common.noData'))
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
              children: [
                Row(children: [
                  Expanded(child: StatCard(label: t('insurance.coverageAmount'), value: money(acc.coverageAmount, compact: true))),
                  const SizedBox(width: 10),
                  Expanded(child: StatCard(label: t('insurance.totalContributions'), value: money(acc.totalContributed, compact: true), tone: Tone.success)),
                ]),
                const SizedBox(height: 12),
                KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  KCardHeader(title: acc.planName, trailing: StatusBadge(acc.status, label: t('insurance.status.${acc.status}'))),
                  const SizedBox(height: 12),
                  InfoGrid([
                    (t('insurance.monthlyContribution'), Text(money(acc.monthlyContribution))),
                    (t('insurance.coverageAmount'), Text(money(acc.coverageAmount))),
                    (t('insurance.startDate'), Text(fmtDate(acc.startDate))),
                    (t('insurance.endDate'), Text(fmtDate(acc.endDate))),
                  ]),
                ])),
                const SizedBox(height: 12),
                SectionTitle(t('insurance.totalContributions')),
                for (final c in contributions.take(8))
                  Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                      Expanded(child: Text('${c.period} · ${c.reference}', style: const TextStyle(fontSize: 12))),
                      Text(money(c.amount), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5)),
                    ])),
                  ),
                const SizedBox(height: 8),
                SectionTitle(t('nav.claims')),
                if (claims.isEmpty) KCard(child: EmptyState(title: t('common.noData'))),
                for (final c in claims)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(c.claimNumber, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5, color: K.primary700)),
                        Text(c.claimType, style: const TextStyle(fontSize: 11.5, color: K.neutral500)),
                      ])),
                      Text(money(c.amountApproved > 0 ? c.amountApproved : c.amountRequested), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5)),
                      const SizedBox(width: 8),
                      StatusBadge(c.status, label: t('insurance.claimStatus.${c.status}')),
                    ])),
                  ),
              ],
            ),
    );
  }
}
