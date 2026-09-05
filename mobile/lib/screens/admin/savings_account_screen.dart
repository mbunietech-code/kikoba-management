import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/charts.dart';
import '../../widgets/ui.dart';

class SavingsAccountScreen extends StatelessWidget {
  const SavingsAccountScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final acc = mock.savingsAccounts.where((a) => a.id == id).firstOrNull;
    if (acc == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final txns = mock.savingsTxns.where((s) => s.accountId == id).toList()..sort((a, b) => a.date.compareTo(b.date));
    final deposits = sumI(txns.where((s) => s.type == 'deposit').map((s) => s.amount));
    final withdrawals = sumI(txns.where((s) => s.type == 'withdrawal').map((s) => s.amount));
    final series = txns.map((s) => {'month': fmtDate(s.date, style: 'short'), 'balance': s.balanceAfter}).toList();

    return AppScaffold(
      title: mock.memberName(acc.memberId),
      subtitle: acc.accountNumber,
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('common.balance'), value: money(acc.balance, compact: true), tone: Tone.info)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('savings.totalDeposits'), value: money(deposits, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('savings.totalWithdrawals'), value: money(withdrawals, compact: true), tone: Tone.neutral)),
          ]),
          const SizedBox(height: 14),
          if (series.length > 1)
            KCard(child: AreaTrend(data: series, keys: const ['balance'], colors: const [K.secondary600])),
          const SizedBox(height: 14),
          KCard(padding: EdgeInsets.zero, child: Column(children: [
            for (var i = txns.length - 1; i >= 0; i--) ...[
              if (i != txns.length - 1) const ListDivider(),
              Builder(builder: (_) {
                final s = txns[i];
                final isDep = s.type == 'deposit';
                return ListTile(
                  title: Text(t('savings.txnType.${s.type}'), style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600)),
                  subtitle: Text('${fmtDate(s.date)} · ${s.reference}', style: const TextStyle(fontSize: 11)),
                  trailing: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.end, children: [
                    Text('${isDep ? '+' : '−'}${money(s.amount, symbol: false)}',
                        style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: isDep ? K.tertiary700 : K.danger)),
                    Text(money(s.balanceAfter, compact: true), style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                  ]),
                );
              }),
            ],
          ])),
        ],
      ),
    );
  }
}
