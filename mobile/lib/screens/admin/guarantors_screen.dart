import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class GuarantorsScreen extends StatefulWidget {
  const GuarantorsScreen({super.key});
  @override
  State<GuarantorsScreen> createState() => _GuarantorsScreenState();
}

class _GuarantorsScreenState extends State<GuarantorsScreen> {
  String _status = 'all';
  static const _cap = 5000000;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final rows = mock.guarantors.where((g) => _status == 'all' || g.status == _status).toList();
    final pending = mock.guarantors.where((g) => g.status == 'pending').length;
    final totalGuaranteed = sumI(mock.guarantors.where((g) => g.status == 'approved').map((g) => g.guaranteedAmount));

    int exposure(String gid) => sumI(mock.guarantors.where((g) => g.guarantorId == gid && g.status == 'approved').map((g) => g.guaranteedAmount));

    return AppScaffold(
      title: t('guarantors.title'),
      subtitle: t('guarantors.subtitle'),
      showBackButton: true,
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
          child: Row(children: [
            Expanded(child: StatCard(label: t('guarantors.title'), value: '${mock.guarantors.length}')),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('guarantors.status.pending'), value: '$pending', tone: Tone.neutral)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('guarantors.guaranteedAmount'), value: money(totalGuaranteed, compact: true), tone: Tone.info)),
          ]),
        ),
        SizedBox(
          height: 40,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16), children: [
            for (final s in ['all', 'pending', 'approved', 'rejected', 'released'])
              Padding(
                padding: const EdgeInsets.only(right: 8, top: 4),
                child: ChoiceChip(
                  label: Text(s == 'all' ? t('common.all') : t('guarantors.status.$s')),
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
              final g = rows[i];
              final exp = exposure(g.guarantorId);
              return KCard(
                onTap: () => context.go('/admin/loans/${g.loanId}'),
                padding: const EdgeInsets.all(12),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Text(g.loanNumber, style: const TextStyle(fontWeight: FontWeight.w700, color: K.primary700)),
                    const Spacer(),
                    StatusBadge(g.status, label: t('guarantors.status.${g.status}')),
                  ]),
                  const SizedBox(height: 8),
                  Row(children: [
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(t('guarantors.borrower'), style: const TextStyle(fontSize: 10, color: K.neutral400)),
                      MemberInline(g.borrowerId, dense: true),
                    ])),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(t('guarantors.guarantor'), style: const TextStyle(fontSize: 10, color: K.neutral400)),
                      MemberInline(g.guarantorId, dense: true),
                    ])),
                  ]),
                  const SizedBox(height: 10),
                  Row(children: [
                    Text('${t('guarantors.exposure')}: ', style: const TextStyle(fontSize: 11, color: K.neutral500)),
                    Text('${money(exp, compact: true)} / ${money(_cap, compact: true)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                  ]),
                  const SizedBox(height: 4),
                  KProgress(exp / _cap * 100, tone: exp > _cap ? Tone.danger : Tone.primary),
                ]),
              );
            },
          ),
        ),
      ]),
    );
  }
}
