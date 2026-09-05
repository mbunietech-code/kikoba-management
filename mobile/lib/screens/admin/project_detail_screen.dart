import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class ProjectDetailScreen extends StatelessWidget {
  const ProjectDetailScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final p = mock.projects.where((x) => x.id == id).firstOrNull;
    if (p == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final invs = mock.projectInvestments.where((x) => x.projectId == id).toList();
    final invested = sumI(invs.map((i) => i.amount));
    final progress = p.capitalRequired == 0 ? 0.0 : p.capitalRaised / p.capitalRequired * 100;

    return AppScaffold(
      title: p.name,
      subtitle: t('projects.type.${p.type}'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Text(t('projects.fundingProgress'), style: const TextStyle(fontSize: 12, color: K.neutral500)),
              const Spacer(),
              Text('${progress.round()}%', style: const TextStyle(fontWeight: FontWeight.w700)),
            ]),
            const SizedBox(height: 6),
            KProgress(progress, tone: p.capitalRaised >= p.capitalRequired ? Tone.success : Tone.primary),
            const SizedBox(height: 6),
            Text('${money(p.capitalRaised)} / ${money(p.capitalRequired)}', style: const TextStyle(fontSize: 12.5, color: K.neutral600)),
          ])),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(child: StatCard(label: t('projects.expectedProfit'), value: money(p.expectedProfit, compact: true), tone: Tone.info)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('projects.actualProfit'), value: money(p.actualProfit, compact: true), tone: Tone.success)),
          ]),
          const SizedBox(height: 12),
          KCard(child: InfoGrid([
            (t('projects.manager'), Text(p.manager)),
            (t('projects.startDate'), Text(fmtDate(p.startDate))),
            (t('projects.endDate'), Text(fmtDate(p.endDate))),
            (t('projects.participants'), Text('${p.participantCount}')),
            (t('projects.capitalRaised'), Text(money(invested))),
            (t('common.status'), KBadge(t('projects.status.${p.status}'), tone: p.status == 'active' ? Tone.success : Tone.neutral)),
          ])),
          const SizedBox(height: 12),
          SectionTitle('${t('projects.participants')} (${invs.length})'),
          for (final iv in invs)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: KCard(padding: const EdgeInsets.all(12), child: Row(children: [
                Expanded(child: MemberInline(iv.memberId, dense: true)),
                Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                  Text(money(iv.amount), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                  Text('+ ${money(iv.profitShare, compact: true)}', style: const TextStyle(fontSize: 10.5, color: K.tertiary700)),
                ]),
              ])),
            ),
        ],
      ),
    );
  }
}
