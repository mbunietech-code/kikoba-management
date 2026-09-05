import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class AccountingScreen extends StatelessWidget {
  const AccountingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final totalDebit = sumI(mock.journalEntries.expand((j) => j.lines.map((l) => l.debit)));
    final totalCredit = sumI(mock.journalEntries.expand((j) => j.lines.map((l) => l.credit)));
    final assets = sumI(mock.accounts.where((a) => a.type == 'asset').map((a) => a.balance));
    final liabilities = sumI(mock.accounts.where((a) => a.type == 'liability').map((a) => a.balance));

    return AppScaffold(
      title: t('accounting.title'),
      subtitle: t('accounting.subtitle'),
      showBackButton: true,
      body: DefaultTabController(
        length: 2,
        child: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Row(children: [
              Expanded(child: StatCard(label: t('accounting.types.asset'), value: money(assets, compact: true))),
              const SizedBox(width: 10),
              Expanded(child: StatCard(label: t('accounting.types.liability'), value: money(liabilities, compact: true), tone: Tone.info)),
              const SizedBox(width: 10),
              Expanded(child: StatCard(
                label: t('accounting.totalDebit'),
                value: money(totalDebit, compact: true),
                tone: Tone.neutral,
                hint: totalDebit == totalCredit ? t('accounting.balanced') : t('accounting.unbalanced'),
              )),
            ]),
          ),
          TabBar(
            labelColor: K.primary700, unselectedLabelColor: K.neutral500, indicatorColor: K.primary600,
            tabs: [Tab(text: t('accounting.chartOfAccounts')), Tab(text: t('accounting.journalEntries'))],
          ),
          Expanded(child: TabBarView(children: [
            ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: mock.accounts.length,
              separatorBuilder: (_, __) => const SizedBox(height: 6),
              itemBuilder: (_, i) {
                final a = mock.accounts[i];
                return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                  SizedBox(width: 44, child: Text(a.code, style: const TextStyle(fontFamily: 'monospace', fontSize: 12, color: K.neutral500))),
                  const SizedBox(width: 8),
                  Expanded(child: Text(a.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13))),
                  KBadge(t('accounting.types.${a.type}'), tone: accountTypeTone(a.type)),
                  const SizedBox(width: 8),
                  Text(money(a.balance, compact: true), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5)),
                ]));
              },
            ),
            ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: mock.journalEntries.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (_, i) {
                final j = mock.journalEntries[i];
                final amt = sumI(j.lines.map((l) => l.debit));
                return KCard(
                  onTap: () => context.go('/admin/accounting/journal/${j.id}'),
                  padding: const EdgeInsets.all(12),
                  child: Row(children: [
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(j.reference, style: const TextStyle(fontFamily: 'monospace', fontSize: 12, color: K.primary700)),
                      Text(j.description, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500)),
                      Text('${fmtDate(j.entryDate)} · ${j.postedBy}', style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                    ])),
                    Text(money(amt, compact: true), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                  ]),
                );
              },
            ),
          ])),
        ]),
      ),
    );
  }
}
