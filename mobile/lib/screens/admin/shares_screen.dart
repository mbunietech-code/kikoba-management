import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/charts.dart';
import '../../widgets/ui.dart';

class SharesScreen extends StatelessWidget {
  const SharesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final totalValue = sumI(mock.shares.map((s) => s.totalValue));
    final totalQty = sumI(mock.shares.map((s) => s.quantity));
    final holders = mock.shares.map((s) => s.memberId).toSet().length;
    final trend = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
        .asMap()
        .entries
        .map((e) => {'month': e.value, 'amount': 1800000 + e.key * 520000})
        .toList();
    final rows = [...mock.shares]..sort((a, b) => b.purchasedAt.compareTo(a.purchasedAt));

    return AppScaffold(
      title: t('shares.title'),
      subtitle: t('shares.subtitle'),
      currentRoute: '/admin/shares',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('shares.shareCapital'), value: money(totalValue, compact: true))),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('shares.sharesOutstanding'), value: num0(totalQty), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('shares.holders'), value: '$holders', tone: Tone.info)),
          ]),
          const SizedBox(height: 14),
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('shares.title'), subtitle: t('dashboard.contributionTrend')),
            const SizedBox(height: 12),
            MiniBars(data: trend, color: K.tertiary600),
          ])),
          const SizedBox(height: 14),
          for (final s in rows.take(40))
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(
                padding: const EdgeInsets.all(12),
                child: Row(children: [
                  Expanded(child: MemberInline(s.memberId, dense: true)),
                  Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                    Text(money(s.totalValue), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                    Text('${s.quantity} × ${money(s.pricePerShare, symbol: false)} · ${fmtDate(s.purchasedAt, style: 'short')}',
                        style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                  ]),
                ]),
              ),
            ),
        ],
      ),
    );
  }
}
