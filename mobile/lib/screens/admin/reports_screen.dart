import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

const _reports = [
  ('membership', Icons.groups_rounded, Tone.primary),
  ('shares', Icons.pie_chart_rounded, Tone.success),
  ('savings', Icons.savings_rounded, Tone.info),
  ('loans', Icons.request_quote_rounded, Tone.primary),
  ('profit', Icons.trending_up_rounded, Tone.success),
  ('projects', Icons.bar_chart_rounded, Tone.info),
  ('insurance', Icons.verified_user_rounded, Tone.primary),
  ('financial', Icons.description_rounded, Tone.neutral),
];

class ReportsScreen extends StatelessWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    return AppScaffold(
      title: t('reports.title'),
      subtitle: t('reports.subtitle'),
      showBackButton: true,
      body: GridView.count(
        crossAxisCount: 2,
        padding: const EdgeInsets.all(16),
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 1.05,
        children: [
          for (final r in _reports)
            KCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Container(
                  width: 40, height: 40,
                  decoration: BoxDecoration(color: _bg(r.$3), borderRadius: BorderRadius.circular(11)),
                  child: Icon(r.$2, size: 20, color: _fg(r.$3)),
                ),
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(t('reports.${r.$1}'), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                  const SizedBox(height: 2),
                  Text('${t('reports.asOf')} ${fmtDate(DateTime.now().toIso8601String())}', style: const TextStyle(fontSize: 11, color: K.neutral400)),
                ]),
                OutlinedButton.icon(
                  onPressed: () => toast(context, '${t('reports.${r.$1}')} — ${t('reports.generate')} ✓'),
                  icon: const Icon(Icons.download_rounded, size: 16),
                  label: Text(t('reports.generate'), style: const TextStyle(fontSize: 12)),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), minimumSize: Size.zero),
                ),
              ]),
            ),
        ],
      ),
    );
  }

  Color _bg(Tone tn) => switch (tn) { Tone.primary => K.primary50, Tone.info => K.secondary50, Tone.success => K.tertiary50, _ => K.neutral100 };
  Color _fg(Tone tn) => switch (tn) { Tone.primary => K.primary700, Tone.info => K.secondary700, Tone.success => K.tertiary700, _ => K.neutral600 };
}
