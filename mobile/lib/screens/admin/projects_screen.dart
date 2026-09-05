import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class ProjectsScreen extends StatelessWidget {
  const ProjectsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final active = mock.projects.where((p) => p.status == 'active').length;
    final capital = sumI(mock.projects.map((p) => p.capitalRaised));
    final profit = sumI(mock.projects.map((p) => p.actualProfit));

    return AppScaffold(
      title: t('projects.title'),
      subtitle: t('projects.subtitle'),
      showBackButton: true,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => toast(context, '${t('projects.addProject')} ✓'),
        icon: const Icon(Icons.add), label: Text(t('projects.addProject')),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        children: [
          Row(children: [
            Expanded(child: StatCard(label: t('dashboard.activeProjects'), value: '$active')),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('dashboard.projectCapital'), value: money(capital, compact: true), tone: Tone.info)),
            const SizedBox(width: 10),
            Expanded(child: StatCard(label: t('projects.actualProfit'), value: money(profit, compact: true), tone: Tone.success)),
          ]),
          const SizedBox(height: 14),
          for (final p in mock.projects)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: KCard(
                onTap: () => context.go('/admin/projects/${p.id}'),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Expanded(child: Text(p.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15))),
                    KBadge(t('projects.status.${p.status}'),
                        tone: p.status == 'active' ? Tone.success : p.status == 'completed' ? Tone.info : p.status == 'planned' ? Tone.warning : Tone.neutral),
                  ]),
                  const SizedBox(height: 4),
                  Text(p.description, style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
                  const SizedBox(height: 12),
                  Row(children: [
                    Text(t('projects.fundingProgress'), style: const TextStyle(fontSize: 11, color: K.neutral500)),
                    const Spacer(),
                    Text('${money(p.capitalRaised, compact: true)} / ${money(p.capitalRequired, compact: true)}',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                  ]),
                  const SizedBox(height: 6),
                  KProgress(p.capitalRequired == 0 ? 0 : p.capitalRaised / p.capitalRequired * 100,
                      tone: p.capitalRaised >= p.capitalRequired ? Tone.success : Tone.primary, showLabel: true),
                  const SizedBox(height: 10),
                  Row(children: [
                    Icon(Icons.groups_outlined, size: 15, color: K.neutral400),
                    const SizedBox(width: 4),
                    Text('${p.participantCount}', style: const TextStyle(fontSize: 12, color: K.neutral500)),
                    const Spacer(),
                    Text('${t('projects.type.${p.type}')} · ${fmtDate(p.endDate, style: 'short')}', style: const TextStyle(fontSize: 12, color: K.neutral500)),
                  ]),
                ]),
              ),
            ),
        ],
      ),
    );
  }
}
