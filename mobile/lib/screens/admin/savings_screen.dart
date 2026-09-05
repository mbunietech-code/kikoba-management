import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class SavingsScreen extends StatefulWidget {
  const SavingsScreen({super.key});
  @override
  State<SavingsScreen> createState() => _SavingsScreenState();
}

class _SavingsScreenState extends State<SavingsScreen> {
  String _q = '';

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final deposits = sumI(mock.savingsTxns.where((s) => s.type == 'deposit').map((s) => s.amount));
    final withdrawals = sumI(mock.savingsTxns.where((s) => s.type == 'withdrawal').map((s) => s.amount));
    final rows = mock.savingsAccounts.where((a) {
      final name = mock.memberById(a.memberId)?.fullName ?? '';
      return '$name ${a.accountNumber}'.toLowerCase().contains(_q.toLowerCase());
    }).toList();

    return AppScaffold(
      title: t('savings.title'),
      subtitle: t('savings.subtitle'),
      currentRoute: '/admin/savings',
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800,
        foregroundColor: Colors.white,
        onPressed: () => _sheet(context),
        icon: const Icon(Icons.add),
        label: Text(t('savings.deposit')),
      ),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
          child: Row(children: [
            Expanded(child: StatCard(label: t('savings.totalDeposits'), value: money(deposits, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('savings.totalWithdrawals'), value: money(withdrawals, compact: true), tone: Tone.neutral)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('savings.netSavings'), value: money(deposits - withdrawals, compact: true), tone: Tone.info)),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
          child: TextField(
            onChanged: (v) => setState(() => _q = v),
            decoration: InputDecoration(hintText: t('common.searchPlaceholder'), prefixIcon: const Icon(Icons.search, size: 20)),
          ),
        ),
        Expanded(
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 90),
            itemCount: rows.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (_, i) {
              final a = rows[i];
              return KCard(
                onTap: () => context.go('/admin/savings/${a.id}'),
                padding: const EdgeInsets.all(12),
                child: Row(children: [
                  Expanded(child: MemberInline(a.memberId, dense: true)),
                  Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                    Text(money(a.balance), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                    Text(a.accountNumber, style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                  ]),
                ]),
              );
            },
          ),
        ),
      ]),
    );
  }

  void _sheet(BuildContext context) {
    final t = context.t;
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom, left: 16, right: 16, top: 4),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text(t('savings.recordDeposit'), style: Theme.of(ctx).textTheme.titleLarge),
          const SizedBox(height: 12),
          const TextField(decoration: InputDecoration(hintText: '0'), keyboardType: TextInputType.number),
          const SizedBox(height: 16),
          FilledButton(onPressed: () { Navigator.pop(ctx); toast(context, '${t('common.save')} ✓'); }, child: Text(t('common.save'))),
          const SizedBox(height: 20),
        ]),
      ),
    );
  }
}
