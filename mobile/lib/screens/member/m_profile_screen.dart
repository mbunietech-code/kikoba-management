import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MProfileScreen extends StatelessWidget {
  const MProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final m = currentMember(context);
    final acc = mock.savingsAccounts.where((a) => a.memberId == m.id).firstOrNull;

    Widget field(String label, String value) => Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
            const SizedBox(height: 6),
            TextField(controller: TextEditingController(text: value)),
          ]),
        );

    return AppScaffold(
      title: t('common.profile'),
      subtitle: m.memberNumber,
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(child: Column(children: [
            KAvatar(m.fullName, color: m.avatarColor, size: 64),
            const SizedBox(height: 10),
            Text(m.fullName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
            const SizedBox(height: 4),
            StatusBadge(m.status, label: t('members.status.${m.status}')),
            const SizedBox(height: 10),
            Text('${m.phone}\n${m.email}\n${m.address}', textAlign: TextAlign.center, style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
          ])),
          const SizedBox(height: 12),
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('members.tabs.profile')),
            const SizedBox(height: 12),
            field(t('members.fullName'), m.fullName),
            field(t('common.phone'), m.phone),
            field(t('common.email'), m.email),
            field(t('members.address'), m.address),
            field(t('members.nextOfKin'), m.nextOfKin),
          ])),
          const SizedBox(height: 12),
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('common.details')),
            const SizedBox(height: 12),
            InfoGrid([
              (t('members.memberNumber'), Text(m.memberNumber)),
              (t('savings.accountNumber'), Text(acc?.accountNumber ?? '—')),
              (t('members.registrationDate'), Text(fmtDate(m.registrationDate))),
              (t('common.language'), const LangToggle()),
            ]),
          ])),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: () {
                  context.read<Session>().signOut();
                  context.go('/login');
                },
                icon: const Icon(Icons.logout, size: 18, color: K.danger),
                label: Text(t('common.logout'), style: const TextStyle(color: K.danger)),
                style: OutlinedButton.styleFrom(side: const BorderSide(color: Color(0xFFFECACA))),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(child: FilledButton(onPressed: () => toast(context, '${t('common.saveChanges')} ✓'), child: Text(t('common.saveChanges')))),
          ]),
        ],
      ),
    );
  }
}
