import 'mock_data.dart';
import '../models/models.dart';

int sumI(Iterable<int> xs) => xs.fold(0, (a, b) => a + b);

class GroupSummary {
  final int totalMembers, activeMembers, newMembers, totalShares, totalSavings, disbursed,
      outstanding, repayments, revenue, expenses, netProfit, projectCapital, insuranceContributions;
  final double par;
  final int activeProjects, pendingClaims, membersCovered;
  GroupSummary({
    required this.totalMembers, required this.activeMembers, required this.newMembers,
    required this.totalShares, required this.totalSavings, required this.disbursed,
    required this.outstanding, required this.repayments, required this.revenue, required this.expenses,
    required this.netProfit, required this.projectCapital, required this.insuranceContributions,
    required this.par, required this.activeProjects, required this.pendingClaims, required this.membersCovered,
  });
}

GroupSummary groupSummary() {
  final totalShares = sumI(mock.shares.map((s) => s.totalValue));
  final totalSavings = sumI(mock.savingsAccounts.map((a) => a.balance));
  final disbursed = sumI(mock.loans.where((l) => l.disbursementDate != null).map((l) => l.principal));
  final outstanding = sumI(mock.loans.map((l) => l.outstanding));
  final repayments = sumI(mock.repayments.map((r) => r.totalPaid));
  int acc(String code) => mock.accounts.firstWhere((a) => a.code == code, orElse: () => Account(id: '', code: '', name: '', type: '', balance: 0)).balance;
  final revenue = acc('4000') + acc('4100') + acc('4200');
  final expenses = sumI(mock.accounts.where((a) => a.type == 'expense').map((a) => a.balance));
  final parLoans = mock.loans.where((l) => l.status == 'overdue' || l.status == 'defaulted');
  final par = outstanding == 0 ? 0.0 : sumI(parLoans.map((l) => l.outstanding)) / outstanding;
  return GroupSummary(
    totalMembers: mock.members.length,
    activeMembers: mock.activeMembers.length,
    newMembers: mock.members.where((m) => DateTime.now().difference(DateTime.parse(m.registrationDate)).inDays < 60).length,
    totalShares: totalShares, totalSavings: totalSavings, disbursed: disbursed, outstanding: outstanding,
    repayments: repayments, revenue: revenue, expenses: expenses, netProfit: revenue - expenses,
    projectCapital: sumI(mock.projects.map((p) => p.capitalRaised)),
    insuranceContributions: sumI(mock.insuranceContributions.map((c) => c.amount)),
    par: par,
    activeProjects: mock.projects.where((p) => p.status == 'active').length,
    pendingClaims: mock.insuranceClaims.where((c) => c.status == 'submitted' || c.status == 'under_review').length,
    membersCovered: mock.insuranceAccounts.where((a) => a.status == 'active').length,
  );
}

List<Map<String, dynamic>> cashFlowSeries() {
  const months = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
  return List.generate(months.length, (i) => {
        'month': months[i],
        'inflow': 4200000 + i * 380000 + (i.isOdd ? 600000 : 0),
        'outflow': 3100000 + i * 240000 + (i % 3 != 0 ? 300000 : 0),
      });
}

List<Map<String, dynamic>> savingsVsLoansSeries() {
  const months = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
  return List.generate(months.length, (i) => {
        'month': months[i],
        'savings': 52000000 + i * 3400000,
        'loans': 40000000 + i * 3900000,
      });
}

List<Map<String, dynamic>> contributionTrend() {
  const months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
  return List.generate(months.length, (i) => {'month': months[i], 'amount': 620000 + i * 45000 + (i.isOdd ? 40000 : 0)});
}

Map<String, int> loanStatusBreakdown() {
  final map = <String, int>{};
  for (final l in mock.loans) {
    map[l.status] = (map[l.status] ?? 0) + 1;
  }
  return map;
}

class MemberPosition {
  final int shareValue, shareQty, savingsBalance, loanOutstanding, projectInvestment, projectReturn, profit;
  final String? savingsAccountId;
  final Loan? activeLoan;
  final InsuranceAccount? insurance;
  final List<Loan> loans;
  final List<ProjectInvestment> investments;
  MemberPosition({
    required this.shareValue, required this.shareQty, required this.savingsBalance,
    required this.loanOutstanding, required this.projectInvestment, required this.projectReturn,
    required this.profit, this.savingsAccountId, this.activeLoan, this.insurance,
    required this.loans, required this.investments,
  });
}

MemberPosition memberPosition(String memberId) {
  final ms = mock.shares.where((s) => s.memberId == memberId).toList();
  final sa = mock.savingsAccounts.where((a) => a.memberId == memberId).toList();
  final ml = mock.loans.where((l) => l.memberId == memberId).toList();
  final invest = mock.projectInvestments.where((p) => p.memberId == memberId).toList();
  final ins = mock.insuranceAccounts.where((a) => a.memberId == memberId).toList();
  final shareValue = sumI(ms.map((s) => s.totalValue));
  Loan? active;
  for (final l in ml) {
    if (['active', 'overdue', 'disbursed'].contains(l.status)) {
      active = l;
      break;
    }
  }
  return MemberPosition(
    shareValue: shareValue,
    shareQty: sumI(ms.map((s) => s.quantity)),
    savingsBalance: sa.isNotEmpty ? sa.first.balance : 0,
    savingsAccountId: sa.isNotEmpty ? sa.first.id : null,
    activeLoan: active,
    loanOutstanding: sumI(ml.map((l) => l.outstanding)),
    projectInvestment: sumI(invest.map((p) => p.amount)),
    projectReturn: sumI(invest.map((p) => p.amount + p.profitShare)),
    profit: sumI(invest.map((p) => p.profitShare)) + (shareValue / 1000000 * 42000).round(),
    insurance: ins.isNotEmpty ? ins.first : null,
    loans: ml,
    investments: invest,
  );
}

List<Txn> memberTransactions(String memberId) => mock.transactions.where((t) => t.memberId == memberId).toList();
