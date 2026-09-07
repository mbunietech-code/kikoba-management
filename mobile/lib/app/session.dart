import 'package:flutter/foundation.dart';

import '../data/api.dart';
import '../data/api_client.dart';
import '../data/format.dart' as fmt;
import '../data/mock_data.dart';
import '../models/models.dart';

class Session extends ChangeNotifier {
  Role? role;
  String name = '';
  String? memberId;
  String orgName = 'Benja Kikoba';

  /// True while the initial token check / hydration is running at app start.
  bool booting = true;

  /// True while a post-login hydration is in flight.
  bool hydrating = false;

  /// Set after a login that needs an OTP; the OTP screen reads it.
  String? pendingOtpDestination;
  String? pendingOtpDebugCode;

  bool get isAuthed => role != null;
  bool get isStaff => role != null && role != Role.member;

  /* --------------------------- startup --------------------------- */

  Future<void> bootstrap() async {
    booting = true;
    notifyListeners();
    try {
      if (Api.hasSession) {
        final user = await Api.me();
        await _applyUser(user);
      }
    } catch (_) {
      await ApiClient.instance.clear();
      _reset();
    } finally {
      booting = false;
      notifyListeners();
    }
  }

  /* ----------------------------- auth ---------------------------- */

  /// Returns true if signed in; false if an OTP step is now required
  /// (see [pendingOtpDestination]). Throws [ApiException] on failure.
  Future<bool> signIn(String login, String password) async {
    final res = await Api.login(login.trim(), password);
    if (res.needsOtp) {
      pendingOtpDestination = res.otpDestination;
      pendingOtpDebugCode = res.otpDebugCode;
      notifyListeners();
      return false;
    }
    await _applyUser(res.user!);
    notifyListeners();
    return true;
  }

  Future<void> verifyOtp(String code) async {
    final user = await Api.verifyOtp(pendingOtpDestination!, code.trim());
    pendingOtpDestination = null;
    pendingOtpDebugCode = null;
    await _applyUser(user);
    notifyListeners();
  }

  Future<void> signOut() async {
    await Api.logout();
    mock.clear();
    _reset();
    notifyListeners();
  }

  /* --------------------------- internal -------------------------- */

  Future<void> _applyUser(Map<String, dynamic> user) async {
    role = roleFromKey(user['role']?.toString() ??
        (user['roles'] is List && (user['roles'] as List).isNotEmpty ? user['roles'][0].toString() : null));
    name = user['name']?.toString() ?? '';
    memberId = user['member_id']?.toString() ?? user['memberId']?.toString();
    final org = user['organization'];
    if (org is Map) {
      orgName = org['name']?.toString() ?? orgName;
      if (org['currency'] != null) fmt.currencyCode = org['currency'].toString();
    }

    hydrating = true;
    notifyListeners();
    try {
      if (isStaff) {
        await Api.hydrateStaff();
        if (mock.dashboard['organization'] is Map) {
          orgName = mock.dashboard['organization']['name']?.toString() ?? orgName;
        }
      } else if (memberId != null) {
        await Api.hydrateMember(memberId!);
      }
    } catch (_) {
      // leave whatever hydrated; screens degrade to empty states
    } finally {
      hydrating = false;
    }
  }

  Future<void> refresh() async {
    if (role == null) return;
    hydrating = true;
    notifyListeners();
    try {
      if (isStaff) {
        await Api.hydrateStaff();
      } else if (memberId != null) {
        await Api.hydrateMember(memberId!);
      }
    } catch (_) {}
    hydrating = false;
    notifyListeners();
  }

  void _reset() {
    role = null;
    name = '';
    memberId = null;
    pendingOtpDestination = null;
    pendingOtpDebugCode = null;
  }
}
