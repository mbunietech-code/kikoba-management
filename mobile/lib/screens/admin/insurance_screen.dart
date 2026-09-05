import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class InsuranceScreen extends StatefulWidget {
  const InsuranceScreen({super.key});
  @override
  State<InsuranceScreen> createState() => _InsuranceScreenState();
}

class _InsuranceScreenState extends State<InsuranceScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final contributions = sumI(mock.insuranceAccounts.map((a) => a.totalContributed));
    final claimsPaid = sumI(mock.insuranceClaims.where((c) => c.status == 'paid').map((c) => c.amountApproved));
    final covered = mock.insuranceAccounts.where((a) => a.status == 'active').length;

    return AppScaffold(
      title: t('insurance.title'),
      subtitle: t('insurance.subtitle'),
      showBackButton: true,
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
          child: Row(children: [
            Expanded(child: StatCard(label: t('insurance.totalContributions'), value: money(contributions, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('insurance.totalClaims'), value: money(claimsPaid, compact: true), tone: Tone.neutral)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('insurance.activeCoverage'), value: '$covered', tone: Tone.info)),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
          child: SegmentedButton<int>(
            segments: [
              ButtonSegment(value: 0, label: Text('${t('savings.accounts')} (${mock.insuranceAccounts.length})')),
              ButtonSegment(value: 1, label: Text('${t('nav.claims')} (${mock.insuranceClaims.length})')),
            ],
            selected: {_tab},
            onSelectionChanged: (s) => setState(() => _tab = s.first),
            showSelectedIcon: false,
          ),
        ),
        Expanded(
          child: _tab == 0
              ? ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 6, 16, 20),
                  itemCount: mock.insuranceAccounts.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) {
                    final a = mock.insuranceAccounts[i];
                    return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                      Expanded(child: MemberInline(a.memberId, dense: true)),
                      Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                        Text(money(a.totalContributed), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                        Text('${a.planName} · ${t('insurance.status.${a.status}')}', style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                      ]),
                    ]));
                  },
                )
              : ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 6, 16, 20),
                  itemCount: mock.insuranceClaims.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) {
                    final c = mock.insuranceClaims[i];
                    return KCard(padding: const EdgeInsets.all(12), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(children: [
                        Text(c.claimNumber, style: const TextStyle(fontWeight: FontWeight.w700, color: K.primary700)),
                        const Spacer(),
                        StatusBadge(c.status, label: t('insurance.claimStatus.${c.status}')),
                      ]),
                      const SizedBox(height: 6),
                      MemberInline(c.memberId, dense: true),
                      const SizedBox(height: 8),
                      Row(children: [
                        Expanded(child: Text('${c.claimType} · ${c.description}', style: const TextStyle(fontSize: 12, color: K.neutral500))),
                        Text(money(c.amountApproved > 0 ? c.amountApproved : c.amountRequested), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                      ]),
                      if (['submitted', 'under_review'].contains(c.status)) ...[
                        const SizedBox(height: 8),
                        Align(alignment: Alignment.centerRight, child: FilledButton(
                          style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8)),
                          onPressed: () => toast(context, '${t('common.approve')} ✓'), child: Text(t('common.approve')))),
                      ],
                    ]));
                  },
                ),
        ),
      ]),
    );
  }
}
