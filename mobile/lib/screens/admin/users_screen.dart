import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class UsersScreen extends StatelessWidget {
  const UsersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    return AppScaffold(
      title: t('users.title'),
      subtitle: t('users.subtitle'),
      showBackButton: true,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => toast(context, '${t('users.addUser')} ✓'),
        icon: const Icon(Icons.person_add_alt_1), label: Text(t('users.addUser')),
      ),
      body: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        itemCount: mock.staffUsers.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, i) {
          final u = mock.staffUsers[i];
          return KCard(padding: const EdgeInsets.all(12), child: Row(children: [
            KAvatar(u.name, color: K.neutral900, size: 40),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(u.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
              Text(u.email, style: const TextStyle(fontSize: 11.5, color: K.neutral400)),
              const SizedBox(height: 6),
              Row(children: [
                KBadge(t('users.roles.${roleKey(u.role)}'), tone: Tone.primary),
                const SizedBox(width: 6),
                Text(u.lastLoginAt == null ? t('users.never') : relTime(u.lastLoginAt!), style: const TextStyle(fontSize: 11, color: K.neutral400)),
              ]),
            ])),
            StatusBadge(u.status),
          ]));
        },
      ),
    );
  }
}
