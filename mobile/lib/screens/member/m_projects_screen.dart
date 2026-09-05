import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MProjectsScreen extends StatelessWidget {
  const MProjectsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = currentMemberId(context);
    final myInvs = mock.projectInvestments.where((p) => p.memberId == id).toList();
    final invested = sumI(myInvs.map((i) => i.amount));
    final returns = sumI(myInvs.map((i) => i.amount + i.profitShare));

    return AppScaffold(
      title: t('nav.myProjects'),
      subtitle: t('projects.subtitle'),
      currentRoute: '/member/projects',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('projects.myInvestment'), value: money(invested, compact: true))),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('projects.totalReturn'), value: money(returns, compact: true), tone: Tone.success)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('common.total'), value: '${myInvs.length}', tone: Tone.info)),
          ]),
          const SizedBox(height: 14),
          if (myInvs.isEmpty) KCard(child: EmptyState(title: t('common.noData'))),
          for (final iv in myInvs)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Builder(builder: (_) {
                final p = mock.projects.firstWhere((x) => x.id == iv.projectId);
                return KCard(
                  onTap: () => context.go('/member/projects/${p.id}'),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Expanded(child: Text(p.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15))),
                      KBadge(t('projects.status.${p.status}'), tone: p.status == 'active' ? Tone.success : p.status == 'completed' ? Tone.info : Tone.neutral),
                    ]),
                    const SizedBox(height: 10),
                    Row(children: [
                      Expanded(child: _kv(t('projects.myInvestment'), money(iv.amount))),
                      Expanded(child: _kv(t('projects.profitShare'), money(iv.profitShare))),
                    ]),
                    const SizedBox(height: 10),
                    KProgress(p.capitalRequired == 0 ? 0 : p.capitalRaised / p.capitalRequired * 100, showLabel: true,
                        tone: p.capitalRaised >= p.capitalRequired ? Tone.success : Tone.primary),
                  ]),
                );
              }),
            ),
        ],
      ),
    );
  }

  Widget _kv(String k, String v) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(k.toUpperCase(), style: const TextStyle(fontSize: 9.5, color: K.neutral400, fontWeight: FontWeight.w600)),
        Text(v, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      ]);
}
