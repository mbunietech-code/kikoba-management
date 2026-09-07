import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/api.dart';
import '../../data/api_client.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MProfileScreen extends StatefulWidget {
  const MProfileScreen({super.key});

  @override
  State<MProfileScreen> createState() => _MProfileScreenState();
}

class _MProfileScreenState extends State<MProfileScreen> {
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _address = TextEditingController();
  final _nok = TextEditingController();
  final _nokPhone = TextEditingController();
  bool _saving = false;
  bool _init = false;

  @override
  void dispose() {
    for (final c in [_phone, _email, _address, _nok, _nokPhone]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    final session = context.read<Session>();
    try {
      await Api.updateProfile({
        'phone': _phone.text.trim(),
        if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
        'address': _address.text.trim(),
        'next_of_kin': _nok.text.trim(),
        'next_of_kin_phone': _nokPhone.text.trim(),
      });
      await session.refresh();
      if (mounted) toast(context, '${context.t('common.saveChanges')} ✓');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final m = currentMember(context);
    final acc = mock.savingsAccounts.where((a) => a.memberId == m.id).firstOrNull;

    if (!_init) {
      _phone.text = m.phone;
      _email.text = m.email;
      _address.text = m.address;
      _nok.text = m.nextOfKin;
      _nokPhone.text = m.nextOfKinPhone;
      _init = true;
    }

    Widget field(String label, TextEditingController c, {bool enabled = true}) => Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
            const SizedBox(height: 6),
            TextField(controller: c, enabled: enabled),
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
          ])),
          const SizedBox(height: 12),
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('members.tabs.profile')),
            const SizedBox(height: 12),
            field(t('members.fullName'), TextEditingController(text: m.fullName), enabled: false),
            field(t('common.phone'), _phone),
            field(t('common.email'), _email),
            field(t('members.address'), _address),
            field(t('members.nextOfKin'), _nok),
            field(t('members.nextOfKinPhone'), _nokPhone),
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
                onPressed: () => context.read<Session>().signOut(),
                icon: const Icon(Icons.logout, size: 18, color: K.danger),
                label: Text(t('common.logout'), style: const TextStyle(color: K.danger)),
                style: OutlinedButton.styleFrom(side: const BorderSide(color: Color(0xFFFECACA))),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : Text(t('common.saveChanges')),
              ),
            ),
          ]),
        ],
      ),
    );
  }
}
