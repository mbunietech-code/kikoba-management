import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class PaymentsScreen extends StatefulWidget {
  const PaymentsScreen({super.key});
  @override
  State<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends State<PaymentsScreen> {
  String _status = 'all';

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final rows = mock.payments.where((p) => _status == 'all' || p.status == _status).toList()
      ..sort((a, b) => b.paidAt.compareTo(a.paidAt));
    final successful = sumI(mock.payments.where((p) => p.status == 'successful').map((p) => p.amount));
    final pending = mock.payments.where((p) => p.status == 'pending').length;

    return AppScaffold(
      title: t('payments.title'),
      subtitle: t('payments.subtitle'),
      showBackButton: true,
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
          child: Row(children: [
            Expanded(child: StatCard(label: t('payments.status.successful'), value: money(successful, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('payments.status.pending'), value: '$pending', tone: Tone.neutral)),
          ]),
        ),
        SizedBox(
          height: 40,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16), children: [
            for (final s in ['all', 'successful', 'pending', 'failed', 'reversed'])
              Padding(
                padding: const EdgeInsets.only(right: 8, top: 4),
                child: ChoiceChip(
                  label: Text(s == 'all' ? t('common.all') : t('payments.status.$s')),
                  selected: _status == s,
                  onSelected: (_) => setState(() => _status = s),
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
              final p = rows[i];
              return KCard(padding: const EdgeInsets.all(12), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(child: Text(p.internalRef, style: const TextStyle(fontSize: 12, color: K.neutral500))),
                  StatusBadge(p.status, label: t('payments.status.${p.status}')),
                ]),
                const SizedBox(height: 6),
                MemberInline(p.memberId, dense: true),
                const SizedBox(height: 8),
                Row(children: [
                  KBadge(t('payments.methods.${p.method}'), tone: Tone.info),
                  const SizedBox(width: 6),
                  Text(p.provider, style: const TextStyle(fontSize: 11.5, color: K.neutral500)),
                  const Spacer(),
                  Text(money(p.amount), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                ]),
                if (p.status == 'pending') ...[
                  const SizedBox(height: 8),
                  Align(alignment: Alignment.centerRight, child: FilledButton(
                    style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8)),
                    onPressed: () => toast(context, '${t('payments.verify')} ✓'), child: Text(t('payments.verify')))),
                ],
              ]));
            },
          ),
        ),
      ]),
    );
  }
}
