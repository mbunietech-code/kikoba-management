import 'package:flutter/material.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';

const _permissions = [
  'members.view', 'members.create', 'members.update',
  'shares.view', 'shares.create',
  'savings.view', 'savings.deposit', 'savings.withdraw',
  'loans.view', 'loans.approve', 'loans.reject', 'loans.disburse',
  'payments.view', 'payments.verify',
  'accounting.view', 'accounting.post',
  'reports.view', 'settings.manage', 'users.manage', 'audit.view',
];

Map<Role, Set<String>> _rolePerms = {
  Role.superAdmin: _permissions.toSet(),
  Role.admin: _permissions.where((p) => p != 'accounting.post').toSet(),
  Role.treasurer: {'savings.view', 'savings.deposit', 'savings.withdraw', 'payments.view', 'payments.verify', 'reports.view', 'members.view', 'loans.view'},
  Role.accountant: {'accounting.view', 'accounting.post', 'reports.view', 'payments.view', 'members.view', 'loans.view', 'savings.view'},
  Role.loanOfficer: {'loans.view', 'loans.approve', 'loans.reject', 'members.view', 'reports.view'},
  Role.member: {'members.view'},
};

class RolesScreen extends StatefulWidget {
  const RolesScreen({super.key});
  @override
  State<RolesScreen> createState() => _RolesScreenState();
}

class _RolesScreenState extends State<RolesScreen> {
  Role _active = Role.admin;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final perms = _rolePerms[_active]!;
    return AppScaffold(
      title: t('roles.title'),
      subtitle: t('roles.subtitle'),
      showBackButton: true,
      body: Column(children: [
        SizedBox(
          height: 44,
          child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), children: [
            for (final r in [Role.superAdmin, Role.admin, Role.treasurer, Role.accountant, Role.loanOfficer])
              Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(t('users.roles.${roleKey(r)}')),
                  selected: _active == r,
                  onSelected: (_) => setState(() => _active = r),
                ),
              ),
          ]),
        ),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text('${t('roles.permissions')} — ${t('users.roles.${roleKey(_active)}')} · ${mock.staffUsers.where((u) => u.role == _active).length} ${t('roles.members')}',
                  style: const TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 10),
              for (final p in _permissions)
                CheckboxListTile(
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  controlAffinity: ListTileControlAffinity.leading,
                  value: perms.contains(p),
                  activeColor: K.primary600,
                  onChanged: (v) => setState(() => v == true ? perms.add(p) : perms.remove(p)),
                  title: Text(p, style: const TextStyle(fontFamily: 'monospace', fontSize: 12.5, color: K.neutral700)),
                ),
            ],
          ),
        ),
      ]),
    );
  }
}
