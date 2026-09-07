import '../models/models.dart';

/// In-memory working set for the app. Populated from the live API by
/// [Api.hydrateStaff] / [Api.hydrateMember] after sign-in; empty until then.
///
/// (Named `Mock`/`mock` for historical reasons — screens read `mock.members`
/// etc. It is no longer mock data.)
class Mock {
  Mock._();
  static final Mock i = Mock._();

  bool hydrated = false;

  List<Member> members = [];
  List<Share> shares = [];
  List<SavingsAccount> savingsAccounts = [];
  List<SavingsTxn> savingsTxns = [];
  List<LoanProduct> loanProducts = [];
  List<Loan> loans = [];
  List<ScheduleRow> schedules = [];
  List<LoanRepayment> repayments = [];
  List<Guarantor> guarantors = [];
  List<Project> projects = [];
  List<ProjectInvestment> projectInvestments = [];
  List<InsuranceAccount> insuranceAccounts = [];
  List<InsuranceContribution> insuranceContributions = [];
  List<InsuranceClaim> insuranceClaims = [];
  List<Payment> payments = [];
  List<Txn> transactions = [];
  List<Account> accounts = [];
  List<JournalEntry> journalEntries = [];
  List<ProfitDistribution> profitDistributions = [];
  List<AppNotification> notifications = [];
  List<AuditLog> auditLogs = [];
  List<StaffUser> staffUsers = [];
  String currentMemberId = '';

  /// Dashboard payload straight from `GET /dashboard` (staff only).
  Map<String, dynamic> dashboard = {};

  List<Member> get activeMembers => members.where((m) => m.status == 'active').toList();

  Member? memberById(String id) {
    for (final m in members) {
      if (m.id == id) return m;
    }
    return null;
  }

  String memberName(String id) => memberById(id)?.fullName ?? 'Unknown';

  void clear() {
    hydrated = false;
    members = [];
    shares = [];
    savingsAccounts = [];
    savingsTxns = [];
    loanProducts = [];
    loans = [];
    schedules = [];
    repayments = [];
    guarantors = [];
    projects = [];
    projectInvestments = [];
    insuranceAccounts = [];
    insuranceContributions = [];
    insuranceClaims = [];
    payments = [];
    transactions = [];
    accounts = [];
    journalEntries = [];
    profitDistributions = [];
    notifications = [];
    auditLogs = [];
    staffUsers = [];
    currentMemberId = '';
    dashboard = {};
  }
}

final mock = Mock.i;
