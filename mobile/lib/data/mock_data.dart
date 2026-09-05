import 'package:flutter/material.dart';
import '../models/models.dart';

/// Seeded pseudo-random dataset — mirrors the web app's mock layer.
class Mock {
  Mock._();
  static final Mock i = Mock._().._build();

  int _seed = 20260905;
  double _rnd() {
    _seed = (_seed * 1664525 + 1013904223) % 4294967296;
    return _seed / 4294967296;
  }

  T _pick<T>(List<T> a) => a[(_rnd() * a.length).floor()];
  int _int(int min, int max) => (_rnd() * (max - min + 1)).floor() + min;
  int _round(num n, [int to = 1000]) => (n / to).round() * to;

  final _now = DateTime(2026, 9, 5, 9);
  String _daysAgo(num d) => _now.subtract(Duration(days: d.round())).toIso8601String();
  String _dateAgo(num d) => _daysAgo(d).substring(0, 10);
  String _dateAhead(num d) => _dateAgo(-d);

  static const _first = ['Amina', 'Baraka', 'Neema', 'Juma', 'Fatuma', 'Hamisi', 'Zainabu', 'Rajabu', 'Grace', 'Emmanuel', 'Mwajuma', 'Said', 'Halima', 'Frank', 'Rehema', 'Deo', 'Anna', 'Kelvin', 'Upendo', 'Ibrahim', 'Joyce', 'Salum', 'Doreen', 'Michael'];
  static const _last = ['Mushi', 'Kileo', 'Mrema', 'Shirima', 'Massawe', 'Kimaro', 'Lyimo', 'Moshi', 'Temba', 'Swai', 'Nkya', 'Macha', 'Urio', 'Kessy', 'Mollel', 'Sanga', 'Mbwana', 'Chuwa', 'Minja', 'Kweka'];
  static const _avatar = [Color(0xFF115E59), Color(0xFF2563EB), Color(0xFF16A34A), Color(0xFF0F766E), Color(0xFF1D4ED8), Color(0xFF15803D), Color(0xFF0D9488), Color(0xFF7C3AED), Color(0xFFC2410C), Color(0xFFBE123C)];

  late List<Member> members;
  late List<Member> activeMembers;
  late List<Share> shares;
  late List<SavingsAccount> savingsAccounts;
  late List<SavingsTxn> savingsTxns;
  late List<LoanProduct> loanProducts;
  late List<Loan> loans;
  late List<ScheduleRow> schedules;
  late List<LoanRepayment> repayments;
  late List<Guarantor> guarantors;
  late List<Project> projects;
  late List<ProjectInvestment> projectInvestments;
  late List<InsuranceAccount> insuranceAccounts;
  late List<InsuranceContribution> insuranceContributions;
  late List<InsuranceClaim> insuranceClaims;
  late List<Payment> payments;
  late List<Txn> transactions;
  late List<Account> accounts;
  late List<JournalEntry> journalEntries;
  late List<ProfitDistribution> profitDistributions;
  late List<AppNotification> notifications;
  late List<AuditLog> auditLogs;
  late List<StaffUser> staffUsers;
  late String currentMemberId;

  Member? memberById(String id) {
    for (final m in members) {
      if (m.id == id) return m;
    }
    return null;
  }

  String memberName(String id) => memberById(id)?.fullName ?? 'Unknown';

  void _build() {
    // members
    members = List.generate(24, (i) {
      final name = '${_pick(_first)} ${_pick(_last)}';
      final status = i < 19 ? 'active' : _pick(['pending', 'suspended', 'inactive', 'deceased']);
      return Member(
        id: 'm${i + 1}',
        memberNumber: 'MBR-${(i + 1).toString().padLeft(6, '0')}',
        fullName: name,
        phone: '+2557${_int(10, 89)} ${_int(100, 999)} ${_int(100, 999)}',
        email: '${name.toLowerCase().replaceAll(RegExp(r'[^a-z]'), '.')}@mfano.co.tz',
        gender: _pick(['male', 'female']),
        dateOfBirth: _dateAgo(_int(7000, 20000)),
        address: '${_pick(['Njiro', 'Kilombero', 'Sakina', 'Moshono', 'Sombetini', 'Kaloleni'])}, ${_pick(['Arusha', 'Moshi', 'Dar es Salaam'])}',
        nextOfKin: '${_pick(_first)} ${_pick(_last)}',
        nextOfKinPhone: '+2556${_int(10, 89)} ${_int(100, 999)} ${_int(100, 999)}',
        registrationDate: _dateAgo(_int(30, 900)),
        status: status,
        avatarColor: _avatar[i % _avatar.length],
      );
    });
    activeMembers = members.where((m) => m.status == 'active').toList();
    currentMemberId = activeMembers.first.id;

    // shares
    const sharePrice = 10000;
    shares = [];
    for (var i = 0; i < members.length; i++) {
      final m = members[i];
      final buys = m.status == 'active' ? _int(1, 4) : _int(0, 1);
      for (var b = 0; b < buys; b++) {
        final qty = _int(5, 60);
        shares.add(Share(
          id: 'sh$i-$b', memberId: m.id, quantity: qty, pricePerShare: sharePrice,
          totalValue: qty * sharePrice, purchasedAt: _dateAgo(_int(10, 800)),
          transactionRef: 'TXN-2026-${(shares.length + 1).toString().padLeft(6, '0')}', status: 'confirmed',
        ));
      }
    }

    // savings
    savingsAccounts = List.generate(members.length, (i) => SavingsAccount(
      id: 'sa${i + 1}', memberId: members[i].id, accountNumber: 'SAV-${(i + 1).toString().padLeft(6, '0')}',
      balance: 0, status: members[i].status == 'active' ? 'active' : 'dormant', openedAt: members[i].registrationDate,
    ));
    savingsTxns = [];
    for (final acc in savingsAccounts) {
      var bal = 0;
      final n = _int(6, 16);
      for (var t = 0; t < n; t++) {
        final isW = _rnd() < 0.22 && bal > 100000;
        final amount = isW ? _round(_int(20, 200) * 1000) : _round(_int(30, 350) * 1000);
        final before = bal;
        bal = isW ? bal - amount : bal + amount;
        savingsTxns.add(SavingsTxn(
          id: 'st${savingsTxns.length + 1}', accountId: acc.id, memberId: acc.memberId,
          type: isW ? 'withdrawal' : 'deposit', amount: amount, balanceBefore: before, balanceAfter: bal,
          reference: 'TXN-2026-${(3000 + savingsTxns.length).toString().padLeft(6, '0')}', date: _dateAgo(_int(1, 500)),
        ));
      }
      acc.balance = bal;
    }
    savingsTxns.sort((a, b) => b.date.compareTo(a.date));

    // loan products
    loanProducts = [
      LoanProduct(id: 'lp1', name: 'Normal Loan', description: 'Standard member loan against savings.', minAmount: 100000, maxAmount: 5000000, interestRate: 10, interestMethod: 'reducing', repaymentPeriod: 6, repaymentFrequency: 'monthly', processingFee: 1, insuranceFee: 1, penaltyRate: 5, minSavings: 100000, minShares: 10, requiredGuarantors: 2, status: 'active'),
      LoanProduct(id: 'lp2', name: 'Emergency Loan', description: 'Fast, short-term loan for urgent needs.', minAmount: 50000, maxAmount: 1000000, interestRate: 8, interestMethod: 'flat', repaymentPeriod: 3, repaymentFrequency: 'monthly', processingFee: 1.5, insuranceFee: 1, penaltyRate: 5, minSavings: 50000, minShares: 5, requiredGuarantors: 1, status: 'active'),
      LoanProduct(id: 'lp3', name: 'Project Loan', description: 'Financing for member income-generating projects.', minAmount: 500000, maxAmount: 10000000, interestRate: 12, interestMethod: 'reducing', repaymentPeriod: 12, repaymentFrequency: 'monthly', processingFee: 2, insuranceFee: 1.5, penaltyRate: 5, minSavings: 300000, minShares: 30, requiredGuarantors: 3, status: 'active'),
      LoanProduct(id: 'lp4', name: 'Large Loan', description: 'High-value loan with extended repayment.', minAmount: 5000000, maxAmount: 30000000, interestRate: 14, interestMethod: 'reducing', repaymentPeriod: 24, repaymentFrequency: 'monthly', processingFee: 2.5, insuranceFee: 2, penaltyRate: 6, minSavings: 1000000, minShares: 100, requiredGuarantors: 3, status: 'active'),
    ];

    // loans + schedules + repayments
    const loanStatuses = ['active', 'active', 'active', 'overdue', 'completed', 'under_review', 'submitted', 'approved', 'disbursed', 'rejected', 'defaulted'];
    loans = [];
    schedules = [];
    repayments = [];
    final borrowers = activeMembers.take(16).toList();
    for (var i = 0; i < borrowers.length; i++) {
      final m = borrowers[i];
      final product = _pick(loanProducts);
      final principal = _round(_int((product.minAmount / 1000).round(), (product.maxAmount < 8000000 ? product.maxAmount : 8000000) ~/ 1000) * 1000, 50000);
      final interest = (principal * product.interestRate * product.repaymentPeriod / (100 * 12)).round();
      final fees = (principal * product.processingFee / 100).round();
      final insurance = (principal * product.insuranceFee / 100).round();
      final status = loanStatuses[i % loanStatuses.length];
      final total = principal + interest + fees + insurance;
      final runningLike = ['active', 'overdue', 'completed', 'disbursed', 'defaulted'].contains(status);
      final paidRatio = status == 'completed'
          ? 1.0
          : status == 'overdue'
              ? 0.35
              : status == 'defaulted'
                  ? 0.2
                  : runningLike
                      ? _rnd() * 0.7 + 0.1
                      : 0.0;
      final amountPaid = _round(total * paidRatio, 1000);
      final disbursed = runningLike || status == 'approved';
      final loanId = 'ln${i + 1}';
      final loanNumber = 'LN-${(i + 1).toString().padLeft(6, '0')}';

      loans.add(Loan(
        id: loanId, loanNumber: loanNumber, memberId: m.id, productId: product.id, productName: product.name,
        principal: principal, interest: interest, fees: fees, insurance: insurance,
        penalty: (status == 'overdue' || status == 'defaulted') ? _round(interest * 0.1, 1000) : 0,
        total: total, amountPaid: amountPaid, outstanding: (total - amountPaid) < 0 ? 0 : total - amountPaid,
        status: status,
        purpose: _pick(['Business stock', 'School fees', 'Farm inputs', 'Home improvement', 'Medical', 'Equipment purchase', 'Working capital']),
        period: product.repaymentPeriod, frequency: product.repaymentFrequency, interestMethod: product.interestMethod,
        applicationDate: _dateAgo(_int(20, 400)),
        approvalDate: (disbursed || status == 'under_review') ? _dateAgo(_int(15, 380)) : null,
        disbursementDate: disbursed ? _dateAgo(_int(10, 360)) : null,
        maturityDate: disbursed ? _dateAhead(_int(-60, 300)) : null,
      ));

      if (disbursed) {
        final perInst = (total / product.repaymentPeriod).round();
        var paidLeft = amountPaid;
        for (var k = 1; k <= product.repaymentPeriod; k++) {
          final totalDue = k == product.repaymentPeriod ? total - perInst * (product.repaymentPeriod - 1) : perInst;
          final payToThis = paidLeft < totalDue ? paidLeft : totalDue;
          paidLeft -= payToThis;
          final due = _dateAhead(_int(-40, 260) + k * 30 - 120);
          final rowStatus = payToThis >= totalDue
              ? 'paid'
              : payToThis > 0
                  ? 'partial'
                  : DateTime.parse(due).isBefore(DateTime(2026, 9, 5))
                      ? 'overdue'
                      : 'pending';
          schedules.add(ScheduleRow(
            id: '$loanId-s$k', loanId: loanId, installment: k, dueDate: due,
            principalDue: (principal / product.repaymentPeriod).round(),
            interestDue: (interest / product.repaymentPeriod).round(),
            feeDue: ((fees + insurance) / product.repaymentPeriod).round(),
            penaltyDue: 0, totalDue: totalDue, amountPaid: payToThis, status: rowStatus,
            paidAt: payToThis >= totalDue ? due : null,
          ));
          if (payToThis > 0) {
            repayments.add(LoanRepayment(
              id: '$loanId-r$k', loanId: loanId, memberId: m.id, installment: k,
              principalPaid: (payToThis * (principal / total)).round(),
              interestPaid: (payToThis * (interest / total)).round(),
              feePaid: (payToThis * ((fees + insurance) / total)).round(),
              penaltyPaid: 0, totalPaid: payToThis, date: due,
              reference: 'TXN-2026-${(5000 + repayments.length).toString().padLeft(6, '0')}',
              method: _pick(['mobile_money', 'bank', 'cash']),
            ));
          }
        }
      }
    }

    // guarantors
    guarantors = [];
    for (var i = 0; i < loans.length; i++) {
      final loan = loans[i];
      final product = loanProducts.firstWhere((p) => p.id == loan.productId);
      for (var g = 0; g < product.requiredGuarantors; g++) {
        final guar = _pick(activeMembers.where((m) => m.id != loan.memberId).toList());
        guarantors.add(Guarantor(
          id: 'gr${guarantors.length + 1}', loanId: loan.id, loanNumber: loan.loanNumber,
          borrowerId: loan.memberId, guarantorId: guar.id,
          guaranteedAmount: _round(loan.total / product.requiredGuarantors, 10000),
          status: ['active', 'overdue', 'completed', 'disbursed', 'defaulted'].contains(loan.status) ? 'approved' : _pick(['pending', 'approved']),
          approvedAt: i % 2 == 0 ? _dateAgo(_int(10, 300)) : null,
          createdAt: _dateAgo(_int(15, 320)),
        ));
      }
    }

    // projects
    projects = [
      Project(id: 'pr1', name: 'Maize Bulk Trading', description: 'Buy maize at harvest, store and sell in lean season.', type: 'three_months', capitalRequired: 12000000, capitalRaised: 12000000, expectedProfit: 3000000, actualProfit: 3450000, startDate: _dateAgo(200), endDate: _dateAgo(20), status: 'completed', manager: 'Baraka Kileo', participantCount: 14),
      Project(id: 'pr2', name: 'Poultry Unit', description: 'Broiler production cycle for local hotels.', type: 'monthly', capitalRequired: 6000000, capitalRaised: 4200000, expectedProfit: 1500000, actualProfit: 0, startDate: _dateAgo(25), endDate: _dateAhead(35), status: 'active', manager: 'Neema Mrema', participantCount: 9),
      Project(id: 'pr3', name: 'Boda Boda Fleet', description: 'Five motorcycles on daily rental to riders.', type: 'long_term', capitalRequired: 20000000, capitalRaised: 8500000, expectedProfit: 9000000, actualProfit: 0, startDate: _dateAhead(10), endDate: _dateAhead(375), status: 'planned', manager: 'Juma Shirima', participantCount: 6),
      Project(id: 'pr4', name: 'Hardware Shop Stock', description: 'Restock building materials for peak season.', type: 'three_months', capitalRequired: 15000000, capitalRaised: 15000000, expectedProfit: 4000000, actualProfit: 0, startDate: _dateAgo(40), endDate: _dateAhead(50), status: 'active', manager: 'Grace Massawe', participantCount: 11),
    ];
    projectInvestments = [];
    for (final p in projects) {
      final investors = activeMembers.take(p.participantCount).toList();
      for (final m in investors) {
        final amount = _round(p.capitalRaised / p.participantCount + _int(-200, 300) * 1000, 50000);
        projectInvestments.add(ProjectInvestment(
          id: 'pi${projectInvestments.length + 1}', projectId: p.id, memberId: m.id,
          amount: amount < 50000 ? 50000 : amount,
          profitShare: p.status == 'completed' ? (p.actualProfit / p.participantCount).round() : 0,
          status: p.status == 'completed' ? 'completed' : 'active',
          investedAt: _dateAgo(_int(20, 190)),
        ));
      }
    }

    // insurance
    const insContrib = 20000;
    insuranceAccounts = [];
    for (var i = 0; i < activeMembers.length; i++) {
      final months = _int(4, 20);
      insuranceAccounts.add(InsuranceAccount(
        id: 'ins${i + 1}', memberId: activeMembers[i].id,
        planName: _pick(['Basic Protection', 'Family Cover', 'Standard Plan']),
        monthlyContribution: insContrib,
        coverageAmount: _pick([1000000, 2000000, 3000000]),
        startDate: _dateAgo(months * 30), endDate: _dateAhead(_int(-15, 300)),
        status: i < activeMembers.length - 3 ? 'active' : _pick(['expired', 'suspended']),
        totalContributed: months * insContrib,
      ));
    }
    insuranceContributions = [];
    for (final acc in insuranceAccounts) {
      final months = acc.totalContributed ~/ insContrib;
      for (var k = 0; k < months; k++) {
        final d = DateTime(2026, 9 - k, 1);
        insuranceContributions.add(InsuranceContribution(
          id: 'ic${insuranceContributions.length + 1}', accountId: acc.id, memberId: acc.memberId,
          amount: insContrib, period: d.toIso8601String().substring(0, 7),
          reference: 'TXN-2026-${(7000 + insuranceContributions.length).toString().padLeft(6, '0')}',
          date: d.toIso8601String().substring(0, 10),
        ));
      }
    }
    insuranceClaims = List.generate(7, (i) {
      final m = _pick(activeMembers);
      final requested = _round(_int(200, 1500) * 1000, 50000);
      final status = _pick(['submitted', 'under_review', 'approved', 'rejected', 'paid', 'paid']);
      return InsuranceClaim(
        id: 'cl${i + 1}', claimNumber: 'CLM-${(i + 1).toString().padLeft(5, '0')}', memberId: m.id,
        claimType: _pick(['Medical', 'Funeral', 'Property loss', 'Disability']),
        description: _pick(['Hospitalisation costs', 'Bereavement support', 'Fire damage to shop', 'Accident recovery']),
        amountRequested: requested,
        amountApproved: (status == 'paid' || status == 'approved') ? _round(requested * (_rnd() * 0.4 + 0.6), 10000) : 0,
        status: status, submittedAt: _dateAgo(_int(5, 120)),
        paidAt: status == 'paid' ? _dateAgo(_int(1, 40)) : null,
      );
    });

    // payments
    const providers = {'mobile_money': 'M-Pesa', 'bank': 'CRDB Bank', 'card': 'Selcom', 'cash': 'Cash desk', 'manual': 'Manual entry'};
    payments = List.generate(40, (i) {
      final m = _pick(members);
      final method = _pick(['mobile_money', 'mobile_money', 'bank', 'cash', 'card']);
      final status = _pick(['successful', 'successful', 'successful', 'pending', 'failed', 'reversed']);
      return Payment(
        id: 'pay${i + 1}', memberId: m.id, provider: providers[method]!, method: method,
        amount: _round(_int(20, 800) * 1000, 5000),
        externalRef: '${_pick(['QGH', 'RTX', 'MPX', 'BNK'])}${_int(100000, 999999)}',
        internalRef: 'PMT-2026-${(i + 1).toString().padLeft(6, '0')}', status: status,
        purpose: _pick(['Savings deposit', 'Loan repayment', 'Share purchase', 'Insurance contribution', 'Project investment']),
        paidAt: _daysAgo(_int(0, 90)),
        verifiedAt: status == 'successful' ? _daysAgo(_int(0, 90)) : null,
      );
    });

    // transactions
    const txnTypes = ['savings_deposit', 'savings_withdrawal', 'share_purchase', 'loan_disbursement', 'loan_repayment', 'interest_payment', 'project_investment', 'insurance_payment', 'fee', 'penalty'];
    transactions = List.generate(60, (i) {
      final m = _pick(members);
      final type = _pick(txnTypes);
      return Txn(
        id: 't${i + 1}', reference: 'TXN-2026-${(i + 1).toString().padLeft(6, '0')}', memberId: m.id, type: type,
        amount: _round(_int(10, 900) * 1000, 1000),
        status: _pick(['successful', 'successful', 'successful', 'pending', 'reversed']),
        description: type.replaceAll('_', ' '), createdBy: _pick(['T. Mushi', 'A. Kessy', 'System', 'L. Swai']),
        createdAt: _daysAgo(_int(0, 120)),
      );
    })..sort((a, b) => b.createdAt.compareTo(a.createdAt));

    // accounting
    accounts = [
      Account(id: 'a1', code: '1000', name: 'Cash', type: 'asset', balance: 4250000),
      Account(id: 'a2', code: '1010', name: 'Bank', type: 'asset', balance: 38900000),
      Account(id: 'a3', code: '1300', name: 'Loan Receivable', type: 'asset', balance: 62400000),
      Account(id: 'a4', code: '1400', name: 'Project Investments', type: 'asset', balance: 39700000),
      Account(id: 'a5', code: '1100', name: 'Member Savings', type: 'liability', balance: 71200000),
      Account(id: 'a6', code: '2100', name: 'Insurance Fund', type: 'liability', balance: 8600000),
      Account(id: 'a7', code: '1200', name: 'Member Shares', type: 'equity', balance: 24800000),
      Account(id: 'a8', code: '3000', name: 'Retained Earnings', type: 'equity', balance: 15300000),
      Account(id: 'a9', code: '4000', name: 'Interest Income', type: 'revenue', balance: 9450000),
      Account(id: 'a10', code: '4100', name: 'Project Income', type: 'revenue', balance: 3450000),
      Account(id: 'a11', code: '4200', name: 'Fees Income', type: 'revenue', balance: 1780000),
      Account(id: 'a12', code: '5000', name: 'Insurance Expense', type: 'expense', balance: 2100000),
      Account(id: 'a13', code: '5100', name: 'Operating Expenses', type: 'expense', balance: 3640000),
      Account(id: 'a14', code: '5200', name: 'Project Expenses', type: 'expense', balance: 1200000),
    ];
    final scenarios = [
      [['1000', 'Cash'], ['1100', 'Member Savings'], 'Member savings deposit'],
      [['1010', 'Bank'], ['1300', 'Loan Receivable'], 'Loan repayment received'],
      [['1300', 'Loan Receivable'], ['4000', 'Interest Income'], 'Interest accrued on loans'],
      [['1000', 'Cash'], ['1200', 'Member Shares'], 'Share purchase'],
      [['5100', 'Operating Expenses'], ['1010', 'Bank'], 'Office running costs'],
    ];
    journalEntries = List.generate(18, (i) {
      final s = _pick(scenarios);
      final amt = _round(_int(20, 400) * 1000);
      final d = s[0] as List<String>;
      final c = s[1] as List<String>;
      return JournalEntry(
        id: 'je${i + 1}', reference: 'JE-2026-${(i + 1).toString().padLeft(5, '0')}', description: s[2] as String,
        entryDate: _dateAgo(_int(1, 150)), postedBy: _pick(['A. Kessy (Accountant)', 'T. Mushi (Treasurer)', 'System']),
        transactionRef: 'TXN-2026-${_int(1, 200).toString().padLeft(6, '0')}',
        lines: [
          JournalLine(accountCode: d[0], accountName: d[1], debit: amt, credit: 0),
          JournalLine(accountCode: c[0], accountName: c[1], debit: 0, credit: amt),
        ],
      );
    });

    profitDistributions = [
      ProfitDistribution(id: 'pd1', periodStart: '2025-01-01', periodEnd: '2025-12-31', totalProfit: 14200000, reservedAmount: 2840000, distributableProfit: 11360000, basis: 'shares', status: 'distributed', distributionDate: '2026-01-20'),
      ProfitDistribution(id: 'pd2', periodStart: '2026-01-01', periodEnd: '2026-06-30', totalProfit: 7680000, reservedAmount: 1536000, distributableProfit: 6144000, basis: 'shares', status: 'calculated'),
    ];

    final notifTpl = [
      ['loan_approved', 'Loan approved', 'Loan {ref} has been approved and is ready for disbursement.'],
      ['payment_received', 'Payment received', 'Payment of {amt} recorded against {ref}.'],
      ['payment_overdue', 'Repayment overdue', 'Installment for loan {ref} is overdue by 4 days.'],
      ['savings_confirmed', 'Savings confirmed', 'Your deposit of {amt} has been confirmed.'],
      ['project_completed', 'Project completed', 'Project "Maize Bulk Trading" closed with a profit of TZS 3.45M.'],
      ['profit_distributed', 'Profit distributed', 'Your 2025 profit allocation has been credited.'],
      ['insurance_expiry', 'Insurance expiring', 'Your insurance cover expires in 12 days.'],
    ];
    notifications = List.generate(14, (i) {
      final t = notifTpl[i % notifTpl.length];
      return AppNotification(
        id: 'n${i + 1}', title: t[1], type: t[0], channel: _pick(['in_app', 'sms', 'email', 'push']),
        message: t[2].replaceAll('{ref}', 'LN-${_int(1, 16).toString().padLeft(6, '0')}').replaceAll('{amt}', 'TZS ${_int(50, 400)},000'),
        read: i > 4, createdAt: _daysAgo(i),
      );
    });

    final auditActions = [
      ['APPROVE_LOAN', 'Loan', 'Pending', 'Approved'],
      ['CREATE_MEMBER', 'Member', '', 'Active'],
      ['RECORD_DEPOSIT', 'SavingsTransaction', '', 'Successful'],
      ['UPDATE_SETTINGS', 'Setting', '10%', '12%'],
      ['DISBURSE_LOAN', 'Loan', 'Approved', 'Disbursed'],
      ['REVERSE_TRANSACTION', 'Transaction', 'Successful', 'Reversed'],
      ['VERIFY_PAYMENT', 'Payment', 'Pending', 'Successful'],
      ['REJECT_CLAIM', 'InsuranceClaim', 'Under review', 'Rejected'],
    ];
    auditLogs = List.generate(40, (i) {
      final a = auditActions[i % auditActions.length];
      return AuditLog(
        id: 'al${i + 1}',
        user: _pick(['super.admin@kikoba.co.tz', 't.mushi@kikoba.co.tz', 'a.kessy@kikoba.co.tz', 'l.swai@kikoba.co.tz']),
        action: a[0], entity: a[1], entityId: '${a[1].substring(0, 3).toUpperCase()}-${_int(1, 300).toString().padLeft(6, '0')}',
        oldValue: a[2].isEmpty ? null : a[2], newValue: a[3],
        ipAddress: '196.${_int(0, 255)}.${_int(0, 255)}.${_int(1, 254)}', createdAt: _daysAgo(i * 0.7),
      );
    });

    staffUsers = [
      StaffUser(id: 'u1', name: 'System Owner', email: 'super.admin@kikoba.co.tz', phone: '+255755 000 001', role: Role.superAdmin, status: 'active', lastLoginAt: _daysAgo(0)),
      StaffUser(id: 'u2', name: 'Fatuma Kimaro', email: 'f.kimaro@kikoba.co.tz', phone: '+255755 000 002', role: Role.admin, status: 'active', lastLoginAt: _daysAgo(1)),
      StaffUser(id: 'u3', name: 'Tumaini Mushi', email: 't.mushi@kikoba.co.tz', phone: '+255755 000 003', role: Role.treasurer, status: 'active', lastLoginAt: _daysAgo(0)),
      StaffUser(id: 'u4', name: 'Anna Kessy', email: 'a.kessy@kikoba.co.tz', phone: '+255755 000 004', role: Role.accountant, status: 'active', lastLoginAt: _daysAgo(2)),
      StaffUser(id: 'u5', name: 'Lucas Swai', email: 'l.swai@kikoba.co.tz', phone: '+255755 000 005', role: Role.loanOfficer, status: 'active', lastLoginAt: _daysAgo(3)),
      StaffUser(id: 'u6', name: 'Doreen Minja', email: 'd.minja@kikoba.co.tz', phone: '+255755 000 006', role: Role.loanOfficer, status: 'suspended', lastLoginAt: _daysAgo(30)),
    ];
  }
}

final mock = Mock.i;
