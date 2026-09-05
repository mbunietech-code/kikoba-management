import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class JournalEntryScreen extends StatelessWidget {
  const JournalEntryScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final j = mock.journalEntries.where((x) => x.id == id).firstOrNull;
    if (j == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final td = sumI(j.lines.map((l) => l.debit));
    final tc = sumI(j.lines.map((l) => l.credit));
    final balanced = td == tc;

    return AppScaffold(
      title: j.reference,
      subtitle: j.description,
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(
            padding: EdgeInsets.zero,
            child: Column(children: [
              Padding(
                padding: const EdgeInsets.all(14),
                child: Row(children: [
                  Expanded(child: Text(t('accounting.journalEntries'), style: const TextStyle(fontWeight: FontWeight.w700))),
                  KBadge(balanced ? t('accounting.balanced') : t('accounting.unbalanced'), tone: balanced ? Tone.success : Tone.danger, dot: true),
                ]),
              ),
              const ListDivider(),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                child: Row(children: const [
                  Expanded(flex: 3, child: Text('ACCOUNT', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: K.neutral400))),
                  Expanded(child: Text('DEBIT', textAlign: TextAlign.right, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: K.neutral400))),
                  Expanded(child: Text('CREDIT', textAlign: TextAlign.right, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: K.neutral400))),
                ]),
              ),
              for (final l in j.lines) ...[
                const ListDivider(),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  child: Row(children: [
                    Expanded(flex: 3, child: Text('${l.accountCode}  ${l.accountName}', style: const TextStyle(fontSize: 12.5))),
                    Expanded(child: Text(l.debit == 0 ? '—' : money(l.debit, symbol: false), textAlign: TextAlign.right, style: const TextStyle(fontSize: 12))),
                    Expanded(child: Text(l.credit == 0 ? '—' : money(l.credit, symbol: false), textAlign: TextAlign.right, style: const TextStyle(fontSize: 12))),
                  ]),
                ),
              ],
              const ListDivider(),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                child: Row(children: [
                  Expanded(flex: 3, child: Text(t('common.total'), style: const TextStyle(fontWeight: FontWeight.w700))),
                  Expanded(child: Text(money(td, symbol: false), textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12))),
                  Expanded(child: Text(money(tc, symbol: false), textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12))),
                ]),
              ),
            ]),
          ),
          const SizedBox(height: 12),
          KCard(child: InfoGrid([
            (t('common.reference'), Text(j.reference)),
            (t('accounting.entryDate'), Text(fmtDate(j.entryDate))),
            (t('accounting.postedBy'), Text(j.postedBy)),
            (t('transactions.txnRef'), Text(j.transactionRef)),
          ])),
        ],
      ),
    );
  }
}
