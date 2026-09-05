import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MProjectDetailScreen extends StatelessWidget {
  const MProjectDetailScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final mid = currentMemberId(context);
    final p = mock.projects.where((x) => x.id == id).firstOrNull;
    if (p == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final myInv = mock.projectInvestments.where((x) => x.projectId == id && x.memberId == mid).firstOrNull;
    final progress = p.capitalRequired == 0 ? 0.0 : p.capitalRaised / p.capitalRequired * 100;
    final canInvest = p.status == 'planned' || (p.status == 'active' && progress < 100);

    return AppScaffold(
      title: p.name,
      subtitle: t('projects.type.${p.type}'),
      showBackButton: true,
      floatingActionButton: canInvest
          ? FloatingActionButton.extended(
              backgroundColor: K.primary800, foregroundColor: Colors.white,
              onPressed: () => toast(context, '${t('projects.invest')} ✓'),
              icon: const Icon(Icons.trending_up), label: Text(t('projects.invest')))
          : null,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        children: [
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(p.description, style: const TextStyle(fontSize: 13, color: K.neutral600)),
            const SizedBox(height: 12),
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
          if (myInv != null)
            KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              KCardHeader(title: t('projects.myInvestment')),
              const SizedBox(height: 8),
              Text(money(myInv.amount), style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Row(children: [
                Text('${t('projects.profitShare')}: ', style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
                Text(money(myInv.profitShare), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                const Spacer(),
                Text('${t('projects.totalReturn')}: ', style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
                Text(money(myInv.amount + myInv.profitShare), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800)),
              ]),
            ])),
          const SizedBox(height: 12),
          KCard(child: InfoGrid([
            (t('projects.manager'), Text(p.manager)),
            (t('projects.participants'), Text('${p.participantCount}')),
            (t('projects.startDate'), Text(fmtDate(p.startDate))),
            (t('projects.endDate'), Text(fmtDate(p.endDate))),
            (t('projects.expectedProfit'), Text(money(p.expectedProfit))),
            (t('common.status'), KBadge(t('projects.status.${p.status}'), tone: p.status == 'active' ? Tone.success : Tone.neutral)),
          ])),
        ],
      ),
    );
  }
}
