import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MemberFormScreen extends StatelessWidget {
  const MemberFormScreen({super.key, this.id});
  final String? id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final Member? existing = id == null ? null : mock.memberById(id!);
    final editing = existing != null;

    Widget field(String label, {String? value, TextInputType? type}) => Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
            const SizedBox(height: 6),
            TextField(controller: TextEditingController(text: value ?? ''), keyboardType: type),
          ]),
        );

    return AppScaffold(
      title: editing ? '${t('common.edit')} — ${existing.fullName}' : t('members.addMember'),
      subtitle: t('members.subtitle'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              KCardHeader(title: t('members.tabs.profile')),
              const SizedBox(height: 14),
              field(t('members.fullName'), value: existing?.fullName),
              field(t('common.phone'), value: existing?.phone, type: TextInputType.phone),
              field(t('common.email'), value: existing?.email, type: TextInputType.emailAddress),
              field(t('members.dob'), value: existing?.dateOfBirth),
              field(t('members.address'), value: existing?.address),
            ]),
          ),
          const SizedBox(height: 14),
          KCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              KCardHeader(title: t('members.nextOfKin')),
              const SizedBox(height: 14),
              field(t('common.name'), value: existing?.nextOfKin),
              field(t('members.nextOfKinPhone'), value: existing?.nextOfKinPhone, type: TextInputType.phone),
            ]),
          ),
          const SizedBox(height: 16),
          Row(children: [
            Expanded(child: OutlinedButton(onPressed: () => context.pop(), child: Text(t('common.cancel')))),
            const SizedBox(width: 12),
            Expanded(
              child: FilledButton(
                onPressed: () {
                  toast(context, editing ? '${t('common.saveChanges')} ✓' : '${t('members.addMember')} ✓');
                  context.go(editing ? '/admin/members/$id' : '/admin/members');
                },
                child: Text(editing ? t('common.saveChanges') : t('common.create')),
              ),
            ),
          ]),
        ],
      ),
    );
  }
}
