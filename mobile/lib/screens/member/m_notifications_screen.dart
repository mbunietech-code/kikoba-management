import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/api.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MNotificationsScreen extends StatefulWidget {
  const MNotificationsScreen({super.key});
  @override
  State<MNotificationsScreen> createState() => _MNotificationsScreenState();
}

class _MNotificationsScreenState extends State<MNotificationsScreen> {
  List<AppNotification> get _items => mock.notifications;

  IconData _icon(String ch) => switch (ch) {
        'sms' => Icons.sms_outlined,
        'email' => Icons.mail_outline,
        'push' => Icons.phone_iphone,
        _ => Icons.notifications_none,
      };

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final unread = _items.where((n) => !n.read).length;
    return AppScaffold(
      title: t('nav.notifications'),
      subtitle: '$unread ${t('notifications.unread')}',
      showBackButton: true,
      actions: [
        TextButton(
          onPressed: unread == 0
              ? null
              : () async {
                  final session = context.read<Session>();
                  try {
                    await Api.meMarkAllRead();
                    await session.refresh();
                  } catch (_) {}
                },
          child: Text(t('notifications.markAllRead'), style: const TextStyle(fontSize: 12)),
        ),
      ],
      body: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, i) {
          final n = _items[i];
          return KCard(
            padding: const EdgeInsets.all(12),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Container(
                width: 36, height: 36,
                decoration: BoxDecoration(color: n.read ? K.neutral100 : K.primary100, borderRadius: BorderRadius.circular(10)),
                child: Icon(_icon(n.channel), size: 17, color: n.read ? K.neutral400 : K.primary700),
              ),
              const SizedBox(width: 12),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(n.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                const SizedBox(height: 2),
                Text(n.message, style: const TextStyle(fontSize: 12.5, color: K.neutral600)),
                const SizedBox(height: 6),
                Text(relTime(n.createdAt), style: const TextStyle(fontSize: 11, color: K.neutral400)),
              ])),
            ]),
          );
        },
      ),
    );
  }
}
