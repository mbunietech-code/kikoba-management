import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/api.dart';
import '../../data/api_client.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MemberFormScreen extends StatefulWidget {
  const MemberFormScreen({super.key, this.id});
  final String? id;

  @override
  State<MemberFormScreen> createState() => _MemberFormScreenState();
}

class _MemberFormScreenState extends State<MemberFormScreen> {
  late final Member? _existing = widget.id == null ? null : mock.memberById(widget.id!);

  late final _fullName = TextEditingController(text: _existing?.fullName ?? '');
  late final _phone = TextEditingController(text: _existing?.phone ?? '');
  late final _email = TextEditingController(text: _existing?.email ?? '');
  late final _dob = TextEditingController(text: _existing?.dateOfBirth ?? '');
  late final _address = TextEditingController(text: _existing?.address ?? '');
  late final _nok = TextEditingController(text: _existing?.nextOfKin ?? '');
  late final _nokPhone = TextEditingController(text: _existing?.nextOfKinPhone ?? '');
  String _gender = 'male';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    if (_existing?.gender.isNotEmpty ?? false) _gender = _existing!.gender;
  }

  @override
  void dispose() {
    for (final c in [_fullName, _phone, _email, _dob, _address, _nok, _nokPhone]) {
      c.dispose();
    }
    super.dispose();
  }

  bool get _editing => _existing != null;

  Future<void> _submit() async {
    if (_fullName.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      toast(context, context.t('members.fullName') + ' / ' + context.t('common.phone'));
      return;
    }
    setState(() => _saving = true);
    final session = context.read<Session>();
    final body = {
      'full_name': _fullName.text.trim(),
      'phone': _phone.text.trim(),
      if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
      'gender': _gender,
      if (_dob.text.trim().isNotEmpty) 'date_of_birth': _dob.text.trim(),
      if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(),
      if (_nok.text.trim().isNotEmpty) 'next_of_kin': _nok.text.trim(),
      if (_nokPhone.text.trim().isNotEmpty) 'next_of_kin_phone': _nokPhone.text.trim(),
    };
    try {
      if (_editing) {
        await Api.updateMember(widget.id!, body);
      } else {
        await Api.createMember(body);
      }
      await session.refresh();
      if (!mounted) return;
      toast(context, _editing ? '${context.t('common.saveChanges')} ✓' : '${context.t('members.addMember')} ✓');
      context.go(_editing ? '/admin/members/${widget.id}' : '/admin/members');
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = context.t;

    Widget field(String label, TextEditingController c, {TextInputType? type}) => Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
            const SizedBox(height: 6),
            TextField(controller: c, keyboardType: type),
          ]),
        );

    return AppScaffold(
      title: _editing ? '${t('common.edit')} — ${_existing!.fullName}' : t('members.addMember'),
      subtitle: t('members.subtitle'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              KCardHeader(title: t('members.tabs.profile')),
              const SizedBox(height: 14),
              field(t('members.fullName'), _fullName),
              field(t('common.phone'), _phone, type: TextInputType.phone),
              field(t('common.email'), _email, type: TextInputType.emailAddress),
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(t('members.gender'), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
                  const SizedBox(height: 6),
                  SegmentedButton<String>(
                    segments: [
                      ButtonSegment(value: 'male', label: Text(t('members.male'))),
                      ButtonSegment(value: 'female', label: Text(t('members.female'))),
                    ],
                    selected: {_gender},
                    onSelectionChanged: (s) => setState(() => _gender = s.first),
                  ),
                ]),
              ),
              field(t('members.dob'), _dob),
              field(t('members.address'), _address),
            ]),
          ),
          const SizedBox(height: 14),
          KCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              KCardHeader(title: t('members.nextOfKin')),
              const SizedBox(height: 14),
              field(t('common.name'), _nok),
              field(t('members.nextOfKinPhone'), _nokPhone, type: TextInputType.phone),
            ]),
          ),
          const SizedBox(height: 16),
          Row(children: [
            Expanded(child: OutlinedButton(onPressed: _saving ? null : () => context.pop(), child: Text(t('common.cancel')))),
            const SizedBox(width: 12),
            Expanded(
              child: FilledButton(
                onPressed: _saving ? null : _submit,
                child: _saving
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : Text(_editing ? t('common.saveChanges') : t('common.create')),
              ),
            ),
          ]),
        ],
      ),
    );
  }
}
