import 'dart:async';

import '../models/models.dart';
import 'api_client.dart';
import 'format.dart' as fmt;
import 'mock_data.dart';

/// Result of a sign-in attempt.
class AuthResult {
  AuthResult({this.user, this.otpDestination, this.otpDebugCode});

  /// Set when the login completed and tokens were issued.
  final Map<String, dynamic>? user;

  /// Set when the backend requires an OTP step next.
  final String? otpDestination;
  final String? otpDebugCode;

  bool get needsOtp => otpDestination != null;
}

List<Map<String, dynamic>> _list(dynamic v) => (v as List? ?? [])
    .map((e) => Map<String, dynamic>.from(e as Map))
    .toList();

Map<String, dynamic> _map(dynamic v) =>
    v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};

class Api {
  static final _c = ApiClient.instance;

  /* ------------------------------- auth ------------------------------- */

  static Future<AuthResult> login(String login, String password) async {
    final data = _map(await _c.post('/auth/login',
        body: {'login': login, 'password': password}, auth: false));

    if (data['otp_required'] == true) {
      return AuthResult(
        otpDestination: data['destination']?.toString(),
        otpDebugCode: data['debug_code']?.toString(),
      );
    }
    await _storeTokens(data);
    return AuthResult(user: _map(data['user']));
  }

  static Future<Map<String, dynamic>> verifyOtp(String destination, String code) async {
    final data = _map(await _c.post('/auth/verify-otp',
        body: {'destination': destination, 'code': code, 'purpose': 'login'}, auth: false));
    await _storeTokens(data);
    return _map(data['user']);
  }

  static Future<void> _storeTokens(Map<String, dynamic> data) async {
    if (data['access_token'] != null) {
      await _c.setTokens(
        access: data['access_token'],
        refresh: data['refresh_token'] ?? '',
      );
    }
  }

  static Future<Map<String, dynamic>> me() async => _map(await _c.get('/auth/me'));

  static Future<void> logout() async {
    try {
      await _c.post('/auth/logout');
    } catch (_) {}
    await _c.clear();
  }

  static bool get hasSession => _c.hasSession;

  /* --------------------------- organization --------------------------- */

  static Future<Map<String, dynamic>> organization() async =>
      _map(await _c.get('/organization', auth: false));

  static Future<void> updateOrganization(Map<String, dynamic> body) =>
      _c.put('/organization', body: body);

  /* ----------------------------- hydration ---------------------------- */

  /// Loads the working set for a staff user. Each collection is fetched
  /// independently — a 403 on one (role without that permission) just
  /// leaves that list empty.
  static Future<void> hydrateStaff() async {
    mock.clear();

    Future<void> pull(String label, Future<void> Function() run) async {
      try {
        await run();
      } catch (_) {/* permission or transient — skip */}
    }

    await Future.wait([
      pull('dashboard', () async {
        mock.dashboard = _map(await _c.get('/dashboard'));
      }),
      pull('members', () async {
        mock.members = _list(await _c.getAll('/members')).map(Member.fromJson).toList();
      }),
      pull('shares', () async {
        mock.shares = _list(await _c.getAll('/shares')).map(Share.fromJson).toList();
      }),
      pull('savings', () async {
        mock.savingsAccounts =
            _list(await _c.getAll('/savings')).map(SavingsAccount.fromJson).toList();
      }),
      pull('loan-products', () async {
        mock.loanProducts =
            _list(await _c.getAll('/loan-products')).map(LoanProduct.fromJson).toList();
      }),
      pull('loans', () async {
        mock.loans = _list(await _c.getAll('/loans')).map(Loan.fromJson).toList();
      }),
      pull('guarantors', () async {
        mock.guarantors = _list(await _c.getAll('/guarantors')).map(Guarantor.fromJson).toList();
      }),
      pull('projects', () async {
        mock.projects = _list(await _c.getAll('/projects')).map(Project.fromJson).toList();
      }),
      pull('insurance-accounts', () async {
        mock.insuranceAccounts = _list(await _c.getAll('/insurance/accounts'))
            .map((e) => InsuranceAccount.fromJson(e))
            .toList();
      }),
      pull('insurance-claims', () async {
        mock.insuranceClaims = _list(await _c.getAll('/insurance/claims'))
            .map((e) => InsuranceClaim.fromJson(e))
            .toList();
      }),
      pull('payments', () async {
        mock.payments = _list(await _c.getAll('/payments')).map(Payment.fromJson).toList();
      }),
      pull('transactions', () async {
        mock.transactions =
            _list(await _c.getAll('/transactions')).map(Txn.fromJson).toList();
      }),
      pull('accounts', () async {
        mock.accounts =
            _list(await _c.getAll('/accounting/accounts')).map(Account.fromJson).toList();
      }),
      pull('journal', () async {
        mock.journalEntries =
            _list(await _c.getAll('/accounting/journal')).map(JournalEntry.fromJson).toList();
      }),
      pull('profit', () async {
        mock.profitDistributions = _list(await _c.getAll('/profit-distributions'))
            .map(ProfitDistribution.fromJson)
            .toList();
      }),
      pull('notifications', () async {
        mock.notifications =
            _list(await _c.getAll('/notifications')).map(AppNotification.fromJson).toList();
      }),
      pull('audit', () async {
        mock.auditLogs =
            _list(await _c.getAll('/audit-logs')).map(AuditLog.fromJson).toList();
      }),
      pull('users', () async {
        mock.staffUsers =
            _list(await _c.getAll('/users')).map(StaffUser.fromJson).toList();
      }),
    ]);

    // sync currency for money() formatting
    final org = _map(mock.dashboard['organization']);
    if (org['currency'] != null) fmt.currencyCode = org['currency'];

    mock.hydrated = true;
  }

  /// Loads the working set for a member from `/me/*`, mapped into the same
  /// [mock] structures so the existing member screens/selectors work unchanged.
  static Future<void> hydrateMember(String memberId) async {
    mock.clear();
    mock.currentMemberId = memberId;

    final results = await Future.wait([
      _c.get('/me/position').catchError((_) => <String, dynamic>{}),
      _c.get('/me/shares').catchError((_) => []),
      _c.get('/me/savings').catchError((_) => <String, dynamic>{}),
      _c.get('/me/loans').catchError((_) => []),
      _c.get('/me/repayments').catchError((_) => <String, dynamic>{}),
      _c.get('/me/projects').catchError((_) => []),
      _c.get('/me/insurance').catchError((_) => <String, dynamic>{}),
      _c.get('/me/transactions').catchError((_) => []),
      _c.get('/me/notifications').catchError((_) => []),
      _c.getAll('/loan-products').catchError((_) => []),
    ]);

    mock.loanProducts = _list(results[9]).map(LoanProduct.fromJson).toList();

    final pos = _map(results[0]);
    final m = _map(pos['member']);
    mock.members = [
      Member(
        id: memberId,
        memberNumber: m['memberNumber']?.toString() ?? '',
        fullName: m['fullName']?.toString() ?? '',
        phone: m['phone']?.toString() ?? '',
        email: m['email']?.toString() ?? '',
        gender: m['gender']?.toString() ?? '',
        dateOfBirth: (m['dateOfBirth']?.toString() ?? '').padRight(0),
        address: m['address']?.toString() ?? '',
        nextOfKin: m['nextOfKin']?.toString() ?? '',
        nextOfKinPhone: m['nextOfKinPhone']?.toString() ?? '',
        registrationDate: (m['registrationDate']?.toString() ?? ''),
        status: m['status']?.toString() ?? 'active',
        avatarColor: Member.fromJson({'avatarColor': m['avatarColor']}).avatarColor,
      ),
    ];
    mock.dashboard = pos; // member "position" payload

    mock.shares = _list(results[1])
        .map((e) => Share.fromJson({...e, 'memberId': memberId}))
        .toList();

    final sav = _map(results[2]);
    final acc = _map(sav['account']);
    if (acc.isNotEmpty) {
      mock.savingsAccounts = [
        SavingsAccount.fromJson({...acc, 'memberId': memberId}),
      ];
      mock.savingsTxns = _list(sav['transactions'])
          .map((e) => SavingsTxn.fromJson(e, accountId: acc['id']?.toString() ?? '', memberId: memberId))
          .toList();
    }

    mock.loans = _list(results[3])
        .map((e) => Loan.fromJson({...e, 'memberId': memberId}))
        .toList();

    final rep = _map(results[4]);
    mock.repayments = _list(rep['history'])
        .map((e) => LoanRepayment.fromJson(e, memberId: memberId))
        .toList();
    mock.schedules = _list(rep['upcoming'])
        .map((e) => ScheduleRow.fromJson(e))
        .toList();

    final projInv = _list(results[5]);
    mock.projectInvestments =
        projInv.map((e) => ProjectInvestment.fromJson(e, memberId: memberId)).toList();
    mock.projects = projInv
        .where((e) => e['project'] is Map)
        .map((e) => Project.fromJson(Map<String, dynamic>.from(e['project'])))
        .toList();

    final ins = _map(results[6]);
    final insAcc = _map(ins['account']);
    if (insAcc.isNotEmpty) {
      mock.insuranceAccounts = [InsuranceAccount.fromJson({...insAcc, 'memberId': memberId})];
      mock.insuranceContributions = _list(ins['contributions'])
          .map((e) => InsuranceContribution.fromJson(e, memberId: memberId))
          .toList();
    }
    mock.insuranceClaims = _list(ins['claims'])
        .map((e) => InsuranceClaim.fromJson(e, memberId: memberId))
        .toList();

    mock.transactions = _list(results[7]).map(Txn.fromJson).toList();
    mock.notifications = _list(results[8]).map(AppNotification.fromJson).toList();

    mock.hydrated = true;
  }

  /* ----------------------------- mutations ---------------------------- */

  // members
  static Future<void> createMember(Map<String, dynamic> body) => _c.post('/members', body: body);
  static Future<void> updateMember(String id, Map<String, dynamic> body) =>
      _c.patch('/members/$id', body: body);
  static Future<void> deleteMember(String id) => _c.delete('/members/$id');
  static Future<Map<String, dynamic>> member(String id) async => _map(await _c.get('/members/$id'));

  // shares
  static Future<void> buyShares(Map<String, dynamic> body) => _c.post('/shares', body: body);

  // savings
  static Future<void> deposit(Map<String, dynamic> body) => _c.post('/savings/deposit', body: body);
  static Future<void> withdraw(Map<String, dynamic> body) => _c.post('/savings/withdraw', body: body);
  static Future<Map<String, dynamic>> savingsAccount(String id) async =>
      _map(await _c.get('/savings/$id'));

  // loan products
  static Future<void> createLoanProduct(Map<String, dynamic> body) =>
      _c.post('/loan-products', body: body);
  static Future<void> updateLoanProduct(String id, Map<String, dynamic> body) =>
      _c.patch('/loan-products/$id', body: body);
  static Future<void> deleteLoanProduct(String id) => _c.delete('/loan-products/$id');

  // loans
  static Future<Map<String, dynamic>> loan(String id) async => _map(await _c.get('/loans/$id'));
  static Future<Map<String, dynamic>> meLoan(String id) async => _map(await _c.get('/me/loans/$id'));
  static Future<Map<String, dynamic>> loanQuote(Map<String, dynamic> body) async =>
      _map(await _c.post('/loans/quote', body: body));
  static Future<void> applyLoan(Map<String, dynamic> body) => _c.post('/loans/apply', body: body);
  static Future<void> approveLoan(String id, [Map<String, dynamic>? body]) =>
      _c.post('/loans/$id/approve', body: body ?? {});
  static Future<void> rejectLoan(String id, Map<String, dynamic> body) =>
      _c.post('/loans/$id/reject', body: body);
  static Future<void> disburseLoan(String id, [Map<String, dynamic>? body]) =>
      _c.post('/loans/$id/disburse', body: body ?? {});
  static Future<void> repayLoan(String id, Map<String, dynamic> body) =>
      _c.post('/loans/$id/repay', body: body);
  static Future<void> cancelLoan(String id) => _c.post('/loans/$id/cancel');

  // guarantors
  static Future<void> verifyGuarantor(String id) => _c.post('/guarantors/$id/verify');
  static Future<void> releaseGuarantor(String id) => _c.delete('/guarantors/$id');

  // projects
  static Future<Map<String, dynamic>> project(String id) async => _map(await _c.get('/projects/$id'));
  static Future<void> createProject(Map<String, dynamic> body) => _c.post('/projects', body: body);
  static Future<void> updateProject(String id, Map<String, dynamic> body) =>
      _c.patch('/projects/$id', body: body);
  static Future<void> deleteProject(String id) => _c.delete('/projects/$id');
  static Future<void> investProject(String id, Map<String, dynamic> body) =>
      _c.post('/projects/$id/invest', body: body);

  // insurance
  static Future<void> contribute(Map<String, dynamic> body) =>
      _c.post('/insurance/contributions', body: body);
  static Future<void> fileClaim(Map<String, dynamic> body) => _c.post('/insurance/claims', body: body);
  static Future<void> decideClaim(String id, Map<String, dynamic> body) =>
      _c.post('/insurance/claims/$id/decide', body: body);

  // payments
  static Future<void> verifyPayment(String id) => _c.post('/payments/$id/verify');
  static Future<void> reversePayment(String id) => _c.post('/payments/$id/reverse');

  // transactions
  static Future<void> reverseTransaction(String id, Map<String, dynamic> body) =>
      _c.post('/transactions/$id/reverse', body: body);

  // accounting
  static Future<void> reverseJournal(String id) => _c.post('/accounting/journal/$id/reverse');

  // profit
  static Future<void> deleteProfitDistribution(String id) =>
      _c.delete('/profit-distributions/$id');

  // notifications
  static Future<void> markAllNotificationsRead() => _c.post('/notifications/read-all');
  static Future<void> announce(Map<String, dynamic> body) =>
      _c.post('/notifications/announce', body: body);

  // settings / users / roles
  static Future<Map<String, dynamic>> settings() async => _map(await _c.get('/settings'));
  static Future<void> updateSettings(Map<String, dynamic> body) => _c.put('/settings', body: body);
  static Future<void> createUser(Map<String, dynamic> body) => _c.post('/users', body: body);
  static Future<void> updateUser(String id, Map<String, dynamic> body) =>
      _c.patch('/users/$id', body: body);
  static Future<void> deleteUser(String id) => _c.delete('/users/$id');

  // member self-service
  static Future<void> updateProfile(Map<String, dynamic> body) => _c.patch('/me/profile', body: body);
  static Future<void> meMarkAllRead() => _c.post('/notifications/read-all');
}
