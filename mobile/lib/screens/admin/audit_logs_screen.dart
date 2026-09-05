import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class AuditLogsScreen extends StatefulWidget {
  const AuditLogsScreen({super.key});
  @override
  State<AuditLogsScreen> createState() => _AuditLogsScreenState();
}

class _AuditLogsScreenState extends State<AuditLogsScreen> {
  String _q = '';

  Tone _tone(String a) => switch (a) {
        'APPROVE_LOAN' || 'CREATE_MEMBER' || 'RECORD_DEPOSIT' || 'VERIFY_PAYMENT' => Tone.success,
        'DISBURSE_LOAN' => Tone.info,
        'UPDATE_SETTINGS' => Tone.warning,
        _ => Tone.danger,
      };

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final rows = mock.auditLogs
        .where((a) => '${a.user} ${a.action} ${a.entity} ${a.entityId}'.toLowerCase().contains(_q.toLowerCase()))
        .toList();

    return AppScaffold(
      title: t('audit.title'),
      subtitle: t('audit.subtitle'),
      showBackButton: true,
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
          child: TextField(
            onChanged: (v) => setState(() => _q = v),
            decoration: InputDecoration(hintText: t('common.searchPlaceholder'), prefixIcon: const Icon(Icons.search, size: 20)),
          ),
        ),
        Expanded(
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
            itemCount: rows.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (_, i) {
              final a = rows[i];
              return KCard(padding: const EdgeInsets.all(12), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  KBadge(a.action.replaceAll('_', ' '), tone: _tone(a.action)),
                  const Spacer(),
                  Text(fmtDateTime(a.createdAt), style: const TextStyle(fontSize: 11, color: K.neutral400)),
                ]),
                const SizedBox(height: 6),
                Text(a.user, style: const TextStyle(fontFamily: 'monospace', fontSize: 11.5, color: K.neutral600)),
                const SizedBox(height: 4),
                Row(children: [
                  Text('${a.entity} · ${a.entityId}', style: const TextStyle(fontSize: 12)),
                  const Spacer(),
                  if (a.oldValue != null) Text('${a.oldValue} → ', style: const TextStyle(fontSize: 11.5, color: K.neutral400, decoration: TextDecoration.lineThrough)),
                  Text('${a.newValue}', style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: K.neutral700)),
                ]),
              ]));
            },
          ),
        ),
      ]),
    );
  }
}
