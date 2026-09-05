import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class ProfitDistributionScreen extends StatefulWidget {
  const ProfitDistributionScreen({super.key});
  @override
  State<ProfitDistributionScreen> createState() => _ProfitDistributionScreenState();
}

class _ProfitDistributionScreenState extends State<ProfitDistributionScreen> {
  ProfitDistribution _selected = mock.profitDistributions.first;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final totalShares = sumI(mock.shares.map((s) => s.totalValue));
    final allocations = mock.activeMembers.map((m) {
      final v = sumI(mock.shares.where((s) => s.memberId == m.id).map((s) => s.totalValue));
      final p = totalShares == 0 ? 0.0 : v / totalShares * 100;
      return (m, p, (_selected.distributableProfit * p / 100).round());
    }).toList()
      ..sort((a, b) => b.$3.compareTo(a.$3));

    return AppScaffold(
      title: t('profit.title'),
      subtitle: t('profit.subtitle'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          for (final d in mock.profitDistributions)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(
                onTap: () => setState(() => _selected = d),
                padding: const EdgeInsets.all(12),
                child: Row(children: [
                  Icon(_selected.id == d.id ? Icons.radio_button_checked : Icons.radio_button_off, size: 18, color: _selected.id == d.id ? K.primary600 : K.neutral300),
                  const SizedBox(width: 10),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('${fmtDate(d.periodStart, style: 'short')} – ${fmtDate(d.periodEnd, style: 'short')}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    Text('${t('profit.distributableProfit')}: ${money(d.distributableProfit, compact: true)}', style: const TextStyle(fontSize: 11.5, color: K.neutral500)),
                  ])),
                  StatusBadge(d.status, label: t('profit.status.${d.status}')),
                ]),
              ),
            ),
          const SizedBox(height: 10),
          Row(children: [
            Expanded(child: StatCard(label: t('profit.totalProfit'), value: money(_selected.totalProfit, compact: true))),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('profit.reservedAmount'), value: money(_selected.reservedAmount, compact: true), tone: Tone.neutral)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('profit.distributableProfit'), value: money(_selected.distributableProfit, compact: true), tone: Tone.success)),
          ]),
          const SizedBox(height: 14),
          SectionTitle('${t('profit.allocation')} · ${t('profit.basisShares')}'),
          for (final a in allocations.take(15))
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                Expanded(child: MemberInline(a.$1.id, dense: true)),
                Text('${a.$2.toStringAsFixed(2)}%', style: const TextStyle(fontSize: 11.5, color: K.neutral400)),
                const SizedBox(width: 12),
                Text(money(a.$3), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
              ])),
            ),
        ],
      ),
    );
  }
}
