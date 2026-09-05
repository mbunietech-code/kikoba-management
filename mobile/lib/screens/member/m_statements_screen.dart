import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MStatementsScreen extends StatelessWidget {
  const MStatementsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final m = currentMember(context);
    final pos = memberPosition(m.id);
    final history = [
      ('${t('statements.title')} — Q2 2026', '2026-06-30'),
      ('${t('statements.title')} — Q1 2026', '2026-03-31'),
      ('${t('statements.title')} — 2025', '2025-12-31'),
    ];

    return AppScaffold(
      title: t('nav.statements'),
      subtitle: t('statements.subtitle'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('statements.generate')),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: 'year',
              decoration: InputDecoration(labelText: t('statements.period')),
              items: [
                DropdownMenuItem(value: 'month', child: Text(t('common.thisMonth'))),
                DropdownMenuItem(value: 'year', child: Text(t('common.thisYear'))),
                const DropdownMenuItem(value: '2025', child: Text('2025')),
              ],
              onChanged: (_) {},
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: () => toast(context, '${t('statements.generate')} ✓'),
                icon: const Icon(Icons.download_rounded, size: 18),
                label: Text(t('statements.generate')),
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: K.neutral50, borderRadius: BorderRadius.circular(12)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${m.fullName} · ${m.memberNumber}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5)),
                const SizedBox(height: 10),
                InfoGrid([
                  (t('member.shareValue'), Text(money(pos.shareValue))),
                  (t('member.savingsBalance'), Text(money(pos.savingsBalance))),
                  (t('member.outstanding'), Text(money(pos.loanOutstanding))),
                  (t('member.projectInvestment'), Text(money(pos.projectInvestment))),
                  (t('member.myProfit'), Text(money(pos.profit))),
                  (t('statements.closing'), Text(money(pos.shareValue + pos.savingsBalance + pos.projectInvestment))),
                ]),
              ]),
            ),
          ])),
          const SizedBox(height: 12),
          SectionTitle(t('reports.generatedReports')),
          for (final h in history)
            KCard(
              padding: const EdgeInsets.all(12),
              child: Row(children: [
                const Icon(Icons.description_outlined, size: 18, color: K.neutral400),
                const SizedBox(width: 10),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(h.$1, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                  Text(fmtDate(h.$2), style: const TextStyle(fontSize: 11, color: K.neutral400)),
                ])),
                const KBadge('PDF', tone: Tone.neutral),
              ]),
            ),
        ],
      ),
    );
  }
}
