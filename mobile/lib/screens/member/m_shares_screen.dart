import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MSharesScreen extends StatelessWidget {
  const MSharesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = context.watch<Session>().memberId ?? mock.currentMemberId;
    final rows = mock.shares.where((s) => s.memberId == id).toList();
    final totalValue = sumI(rows.map((s) => s.totalValue));
    final totalQty = sumI(rows.map((s) => s.quantity));

    return AppScaffold(
      title: t('nav.myShares'),
      subtitle: t('shares.subtitle'),
      showBackButton: true,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => toast(context, '${t('member.buyShares')} ✓'),
        icon: const Icon(Icons.add), label: Text(t('member.buyShares')),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('shares.sharesHeld'), value: num0(totalQty))),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('member.shareValue'), value: money(totalValue, compact: true), tone: Tone.success)),
          ]),
          const SizedBox(height: 14),
          if (rows.isEmpty) EmptyState(title: t('common.noData')),
          for (final s in rows)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(s.transactionRef, style: const TextStyle(fontSize: 11.5, color: K.neutral500)),
                  Text('${s.quantity} × ${money(s.pricePerShare, symbol: false)}', style: const TextStyle(fontSize: 12.5)),
                  Text(fmtDate(s.purchasedAt), style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                ])),
                Text(money(s.totalValue), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              ])),
            ),
        ],
      ),
    );
  }
}
