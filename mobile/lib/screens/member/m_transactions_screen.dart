import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MTransactionsScreen extends StatefulWidget {
  const MTransactionsScreen({super.key});
  @override
  State<MTransactionsScreen> createState() => _MTransactionsScreenState();
}

class _MTransactionsScreenState extends State<MTransactionsScreen> {
  String _type = 'all';

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final all = memberTransactions(currentMemberId(context));
    final types = ['all', ...{for (final x in all) x.type}];
    final rows = all.where((x) => _type == 'all' || x.type == _type).toList();

    return AppScaffold(
      title: t('nav.transactions'),
      subtitle: t('transactions.subtitle'),
      showBackButton: true,
      body: Column(children: [
        SizedBox(
          height: 44,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), children: [
            for (final ty in types)
              Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(ty == 'all' ? t('common.all') : t('transactions.types.$ty')),
                  selected: _type == ty,
                  onSelected: (_) => setState(() => _type = ty),
                ),
              ),
          ]),
        ),
        Expanded(
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 20),
            itemCount: rows.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (_, i) {
              final tx = rows[i];
              return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                Container(
                  width: 36, height: 36,
                  decoration: BoxDecoration(color: K.neutral100, borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.swap_vert_rounded, size: 18, color: K.neutral500),
                ),
                const SizedBox(width: 12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(t('transactions.types.${tx.type}'), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  Text('${tx.reference} · ${fmtDateTime(tx.createdAt)}', style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
                ])),
                Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                  Text(money(tx.amount), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                  StatusBadge(tx.status),
                ]),
              ]));
            },
          ),
        ),
      ]),
    );
  }
}
