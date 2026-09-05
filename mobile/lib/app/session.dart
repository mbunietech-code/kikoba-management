import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../data/mock_data.dart';
import '../models/models.dart';

class Session extends ChangeNotifier {
  Role? role;
  String name = '';
  String? memberId;

  bool get isAuthed => role != null;
  bool get isStaff => role != null && role != Role.member;

  static const _k = 'benja.session';

  Future<void> load() async {
    final p = await SharedPreferences.getInstance();
    final raw = p.getString(_k);
    if (raw == null) return;
    final parts = raw.split('|');
    role = Role.values.firstWhere((r) => r.name == parts[0], orElse: () => Role.member);
    name = parts.length > 1 ? parts[1] : '';
    memberId = parts.length > 2 && parts[2].isNotEmpty ? parts[2] : null;
    notifyListeners();
  }

  Future<void> _persist() async {
    final p = await SharedPreferences.getInstance();
    if (role == null) {
      await p.remove(_k);
    } else {
      await p.setString(_k, '${role!.name}|$name|${memberId ?? ''}');
    }
  }

  /// Demo sign-in: infer staff vs member from the email address.
  void signIn(String emailOrPhone) {
    final e = emailOrPhone.toLowerCase();
    final staff = mock.staffUsers.where((u) => u.email.toLowerCase() == e).toList();
    if (staff.isNotEmpty) {
      role = staff.first.role;
      name = staff.first.name;
      memberId = null;
    } else if (e.contains('admin') || e.contains('staff') || e.contains('kikoba.co.tz')) {
      role = Role.admin;
      name = mock.staffUsers.firstWhere((u) => u.role == Role.admin).name;
      memberId = null;
    } else {
      role = Role.member;
      memberId = mock.currentMemberId;
      name = mock.memberName(mock.currentMemberId);
    }
    _persist();
    notifyListeners();
  }

  void previewAs(Role r) {
    if (r == Role.member) {
      role = Role.member;
      memberId = mock.currentMemberId;
      name = mock.memberName(mock.currentMemberId);
    } else {
      role = r;
      memberId = null;
      name = mock.staffUsers
          .firstWhere((u) => u.role == r, orElse: () => mock.staffUsers[1])
          .name;
    }
    _persist();
    notifyListeners();
  }

  void signOut() {
    role = null;
    name = '';
    memberId = null;
    _persist();
    notifyListeners();
  }
}
