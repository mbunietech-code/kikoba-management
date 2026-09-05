import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/charts.dart';
import '../../widgets/ui.dart';

class MSavingsScreen extends StatelessWidget {
  const MSavingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = context.watch<Session>().memberId ?? mock.currentMemberId;
    final acc = mock.savingsAccounts.where((a) => a.memberId == id).firstOrNull;
    final txns = mock.savingsTxns.where((s) => s.memberId == id).toList()..sort((a, b) => a.date.compareTo(b.date));
    final deposits = sumI(txns.where((s) => s.type == 'deposit').map((s) => s.amount));
    final withdrawals = sumI(txns.where((s) => s.type == 'withdrawal').map((s) => s.amount));
    final series = txns.map((s) => {'month': fmtDate(s.date, style: 'short'), 'balance': s.balanceAfter}).toList();

    return AppScaffold(
      title: t('nav.mySavings'),
      subtitle: acc?.accountNumber,
      currentRoute: '/member/savings',
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => toast(context, '${t('member.makeDeposit')} ✓'),
        icon: const Icon(Icons.add), label: Text(t('member.makeDeposit')),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('common.balance'), value: money(acc?.balance ?? 0, compact: true), tone: Tone.info)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('savings.totalDeposits'), value: money(deposits, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('savings.totalWithdrawals'), value: money(withdrawals, compact: true), tone: Tone.neutral)),
          ]),
          const SizedBox(height: 14),
          if (series.length > 1) KCard(child: AreaTrend(data: series, keys: const ['balance'], colors: const [K.secondary600])),
          const SizedBox(height: 14),
          for (var i = txns.length - 1; i >= 0; i--)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Builder(builder: (_) {
                final s = txns[i];
                final isDep = s.type == 'deposit';
                return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(t('savings.txnType.${s.type}'), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    Text('${fmtDate(s.date)} · ${s.reference}', style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                  ])),
                  Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                    Text('${isDep ? '+' : '−'}${money(s.amount, symbol: false)}', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: isDep ? K.tertiary700 : K.danger)),
                    Text(money(s.balanceAfter, compact: true), style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                  ]),
                ]));
              }),
            ),
        ],
      ),
    );
  }
}
