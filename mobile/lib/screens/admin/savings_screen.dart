import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show FilteringTextInputFormatter;
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/api.dart';
import '../../data/api_client.dart';
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
    final session = context.read<Session>();
    if (mock.savingsAccounts.isEmpty) {
      toast(context, t('common.noData'));
      return;
    }
    final amount = TextEditingController();
    String accountId = mock.savingsAccounts.first.id;
    bool withdrawal = false;
    bool busy = false;

    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom + 20, left: 16, right: 16, top: 4),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text(withdrawal ? t('savings.recordWithdrawal') : t('savings.recordDeposit'),
                style: Theme.of(ctx).textTheme.titleLarge),
            const SizedBox(height: 14),
            SegmentedButton<bool>(
              segments: [
                ButtonSegment(value: false, label: Text(t('savings.deposit'))),
                ButtonSegment(value: true, label: Text(t('savings.withdraw'))),
              ],
              selected: {withdrawal},
              onSelectionChanged: (s) => setSheet(() => withdrawal = s.first),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: accountId,
              isExpanded: true,
              decoration: InputDecoration(labelText: t('savings.accounts')),
              items: mock.savingsAccounts
                  .map((a) => DropdownMenuItem(
                        value: a.id,
                        child: Text('${mock.memberById(a.memberId)?.fullName ?? a.accountNumber} · ${money(a.balance, compact: true)}',
                            overflow: TextOverflow.ellipsis),
                      ))
                  .toList(),
              onChanged: (v) => setSheet(() => accountId = v!),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: amount,
              decoration: InputDecoration(labelText: t('common.amount'), hintText: '0'),
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: busy
                  ? null
                  : () async {
                      final amt = int.tryParse(amount.text.trim()) ?? 0;
                      if (amt <= 0) return;
                      setSheet(() => busy = true);
                      try {
                        final body = {'account_id': accountId, 'amount': amt};
                        if (withdrawal) {
                          await Api.withdraw(body);
                        } else {
                          await Api.deposit(body);
                        }
                        if (ctx.mounted) Navigator.pop(ctx);
                        await session.refresh();
                        if (context.mounted) toast(context, '${t('common.save')} ✓');
                      } on ApiException catch (e) {
                        setSheet(() => busy = false);
                        if (ctx.mounted) toast(ctx, e.message);
                      }
                    },
              child: busy
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : Text(t('common.save')),
            ),
          ]),
        ),
      ),
    );
  }
}
