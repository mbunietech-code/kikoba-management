import 'package:flutter/material.dart';

enum Role { superAdmin, admin, treasurer, accountant, loanOfficer, member }

String roleKey(Role r) => switch (r) {
      Role.superAdmin => 'super_admin',
      Role.admin => 'admin',
      Role.treasurer => 'treasurer',
      Role.accountant => 'accountant',
      Role.loanOfficer => 'loan_officer',
      Role.member => 'member',
    };

Role roleFromKey(String? k) => switch (k) {
      'super_admin' => Role.superAdmin,
      'admin' => Role.admin,
      'treasurer' => Role.treasurer,
      'accountant' => Role.accountant,
      'loan_officer' => Role.loanOfficer,
      _ => Role.member,
    };

/* ------------------------- JSON parsing helpers ------------------------- */

int _i(dynamic v) {
  if (v == null) return 0;
  if (v is int) return v;
  if (v is double) return v.round();
  return int.tryParse(v.toString().split('.').first) ?? 0;
}

double _d(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? 0;
}

String _s(dynamic v, [String fallback = '']) => v?.toString() ?? fallback;
String? _sn(dynamic v) => v?.toString();

String _dateStr(dynamic v) {
  final s = v?.toString();
  if (s == null || s.isEmpty) return '';
  return s.length >= 10 ? s.substring(0, 10) : s;
}

String? _dateStrN(dynamic v) {
  final s = _dateStr(v);
  return s.isEmpty ? null : s;
}

Color _color(dynamic v) {
  final s = v?.toString().replaceAll('#', '') ?? '';
  if (s.length == 6) {
    final n = int.tryParse(s, radix: 16);
    if (n != null) return Color(0xFF000000 | n);
  }
  return const Color(0xFF115E59);
}

/* -------------------------------- models -------------------------------- */

class Member {
  final String id, memberNumber, fullName, phone, email, gender, dateOfBirth, address;
  final String nextOfKin, nextOfKinPhone, registrationDate, status;
  final Color avatarColor;
  Member({
    required this.id, required this.memberNumber, required this.fullName, required this.phone,
    required this.email, required this.gender, required this.dateOfBirth, required this.address,
    required this.nextOfKin, required this.nextOfKinPhone, required this.registrationDate,
    required this.status, required this.avatarColor,
  });

  factory Member.fromJson(Map<String, dynamic> j) => Member(
        id: _s(j['id']),
        memberNumber: _s(j['memberNumber']),
        fullName: _s(j['fullName']),
        phone: _s(j['phone']),
        email: _s(j['email']),
        gender: _s(j['gender']),
        dateOfBirth: _dateStr(j['dateOfBirth']),
        address: _s(j['address']),
        nextOfKin: _s(j['nextOfKin']),
        nextOfKinPhone: _s(j['nextOfKinPhone']),
        registrationDate: _dateStr(j['registrationDate']),
        status: _s(j['status'], 'active'),
        avatarColor: _color(j['avatarColor']),
      );
}

class Share {
  final String id, memberId, transactionRef, purchasedAt, status;
  final int quantity, pricePerShare, totalValue;
  Share({required this.id, required this.memberId, required this.quantity, required this.pricePerShare,
    required this.totalValue, required this.purchasedAt, required this.transactionRef, required this.status});

  factory Share.fromJson(Map<String, dynamic> j) => Share(
        id: _s(j['id']),
        memberId: _s(j['memberId']),
        quantity: _i(j['quantity']),
        pricePerShare: _i(j['pricePerShare']),
        totalValue: _i(j['totalValue']),
        purchasedAt: _dateStr(j['purchasedAt']),
        transactionRef: _s(j['transactionRef']),
        status: _s(j['status'], 'confirmed'),
      );
}

class SavingsAccount {
  final String id, memberId, accountNumber, status, openedAt;
  int balance;
  SavingsAccount({required this.id, required this.memberId, required this.accountNumber,
    required this.balance, required this.status, required this.openedAt});

  factory SavingsAccount.fromJson(Map<String, dynamic> j) => SavingsAccount(
        id: _s(j['id']),
        memberId: _s(j['memberId']),
        accountNumber: _s(j['accountNumber']),
        balance: _i(j['balance']),
        status: _s(j['status'], 'active'),
        openedAt: _dateStr(j['openedAt']),
      );
}

class SavingsTxn {
  final String id, accountId, memberId, type, reference, date;
  final int amount, balanceBefore, balanceAfter;
  SavingsTxn({required this.id, required this.accountId, required this.memberId, required this.type,
    required this.amount, required this.balanceBefore, required this.balanceAfter,
    required this.reference, required this.date});

  factory SavingsTxn.fromJson(Map<String, dynamic> j, {String accountId = '', String memberId = ''}) => SavingsTxn(
        id: _s(j['id']),
        accountId: _s(j['accountId'], accountId),
        memberId: _s(j['memberId'], memberId),
        type: _s(j['type']),
        amount: _i(j['amount']),
        balanceBefore: _i(j['balanceBefore']),
        balanceAfter: _i(j['balanceAfter']),
        reference: _s(j['reference']),
        date: _dateStr(j['date'] ?? j['createdAt']),
      );
}

class LoanProduct {
  final String id, name, description, interestMethod, repaymentFrequency, status;
  final int minAmount, maxAmount, repaymentPeriod, minSavings, minShares, requiredGuarantors;
  final double interestRate, processingFee, insuranceFee, penaltyRate;
  LoanProduct({required this.id, required this.name, required this.description, required this.minAmount,
    required this.maxAmount, required this.interestRate, required this.interestMethod,
    required this.repaymentPeriod, required this.repaymentFrequency, required this.processingFee,
    required this.insuranceFee, required this.penaltyRate, required this.minSavings, required this.minShares,
    required this.requiredGuarantors, required this.status});

  factory LoanProduct.fromJson(Map<String, dynamic> j) => LoanProduct(
        id: _s(j['id']),
        name: _s(j['name']),
        description: _s(j['description']),
        minAmount: _i(j['minimumAmount'] ?? j['minAmount']),
        maxAmount: _i(j['maximumAmount'] ?? j['maxAmount']),
        interestRate: _d(j['interestRate']),
        interestMethod: _s(j['interestMethod'], 'reducing'),
        repaymentPeriod: _i(j['maximumPeriod'] ?? j['repaymentPeriod'] ?? j['defaultPeriod']),
        repaymentFrequency: _s(j['repaymentFrequency'], 'monthly'),
        processingFee: _d(j['processingFee']),
        insuranceFee: _d(j['insuranceFee']),
        penaltyRate: _d(j['penaltyRate']),
        minSavings: _i(j['minimumSavings'] ?? j['minSavings']),
        minShares: _i(j['minimumShares'] ?? j['minShares']),
        requiredGuarantors: _i(j['requiredGuarantors']),
        status: _s(j['status'], 'active'),
      );
}

class Loan {
  final String id, loanNumber, memberId, productId, productName, status, purpose, frequency, interestMethod;
  final int principal, interest, fees, insurance, penalty, total, amountPaid, outstanding, period;
  final String applicationDate;
  final String? approvalDate, disbursementDate, maturityDate;
  final String? memberName;
  final Color? memberColor;
  Loan({required this.id, required this.loanNumber, required this.memberId, required this.productId,
    required this.productName, required this.principal, required this.interest, required this.fees,
    required this.insurance, required this.penalty, required this.total, required this.amountPaid,
    required this.outstanding, required this.status, required this.purpose, required this.period,
    required this.frequency, required this.interestMethod, required this.applicationDate,
    this.approvalDate, this.disbursementDate, this.maturityDate, this.memberName, this.memberColor});

  factory Loan.fromJson(Map<String, dynamic> j) {
    final m = j['member'] is Map ? j['member'] as Map : null;
    return Loan(
      id: _s(j['id']),
      loanNumber: _s(j['loanNumber']),
      memberId: _s(j['memberId'] ?? m?['id']),
      productId: _s(j['productId'] ?? j['loanProductId']),
      productName: _s(j['productName'] ?? (j['product'] is Map ? j['product']['name'] : '')),
      principal: _i(j['principal'] ?? j['principalAmount']),
      interest: _i(j['interest'] ?? j['interestAmount']),
      fees: _i(j['fees'] ?? j['processingFee']),
      insurance: _i(j['insurance'] ?? j['insuranceAmount']),
      penalty: _i(j['penalty'] ?? j['penaltyAmount']),
      total: _i(j['total'] ?? j['totalAmount']),
      amountPaid: _i(j['amountPaid']),
      outstanding: _i(j['outstanding'] ?? j['outstandingBalance']),
      status: _s(j['status']),
      purpose: _s(j['purpose']),
      period: _i(j['period']),
      frequency: _s(j['frequency'] ?? j['repaymentFrequency'], 'monthly'),
      interestMethod: _s(j['interestMethod'], 'reducing'),
      applicationDate: _dateStr(j['applicationDate']),
      approvalDate: _dateStrN(j['approvalDate']),
      disbursementDate: _dateStrN(j['disbursementDate']),
      maturityDate: _dateStrN(j['maturityDate']),
      memberName: _sn(m?['fullName']),
      memberColor: m?['avatarColor'] != null ? _color(m!['avatarColor']) : null,
    );
  }
}

class ScheduleRow {
  final String id, loanId, status;
  final String dueDate;
  final String? paidAt;
  final int installment, principalDue, interestDue, feeDue, penaltyDue, totalDue, amountPaid;
  ScheduleRow({required this.id, required this.loanId, required this.installment, required this.dueDate,
    required this.principalDue, required this.interestDue, required this.feeDue, required this.penaltyDue,
    required this.totalDue, required this.amountPaid, required this.status, this.paidAt});

  factory ScheduleRow.fromJson(Map<String, dynamic> j, {String loanId = ''}) => ScheduleRow(
        id: _s(j['id']),
        loanId: _s(j['loanId'], loanId),
        installment: _i(j['installment'] ?? j['installmentNumber']),
        dueDate: _dateStr(j['dueDate']),
        principalDue: _i(j['principalDue']),
        interestDue: _i(j['interestDue']),
        feeDue: _i(j['feeDue']),
        penaltyDue: _i(j['penaltyDue']),
        totalDue: _i(j['totalDue']),
        amountPaid: _i(j['amountPaid']),
        status: _s(j['status'], 'pending'),
        paidAt: _dateStrN(j['paidAt']),
      );
}

class LoanRepayment {
  final String id, loanId, memberId, date, reference, method;
  final int installment, principalPaid, interestPaid, feePaid, penaltyPaid, totalPaid;
  final String? loanNumber;
  LoanRepayment({required this.id, required this.loanId, required this.memberId, required this.installment,
    required this.principalPaid, required this.interestPaid, required this.feePaid, required this.penaltyPaid,
    required this.totalPaid, required this.date, required this.reference, required this.method, this.loanNumber});

  factory LoanRepayment.fromJson(Map<String, dynamic> j, {String loanId = '', String memberId = ''}) => LoanRepayment(
        id: _s(j['id']),
        loanId: _s(j['loanId'], loanId),
        memberId: _s(j['memberId'], memberId),
        installment: _i(j['installment'] ?? j['installmentNumber']),
        principalPaid: _i(j['principalPaid']),
        interestPaid: _i(j['interestPaid']),
        feePaid: _i(j['feePaid']),
        penaltyPaid: _i(j['penaltyPaid']),
        totalPaid: _i(j['totalPaid']),
        date: _dateStr(j['date'] ?? j['paymentDate']),
        reference: _s(j['reference']),
        method: _s(j['method'], 'cash'),
        loanNumber: _sn(j['loanNumber']),
      );
}

class Guarantor {
  final String id, loanId, loanNumber, borrowerId, guarantorId, status, createdAt;
  final String? approvedAt, guarantorName;
  final int guaranteedAmount;
  Guarantor({required this.id, required this.loanId, required this.loanNumber, required this.borrowerId,
    required this.guarantorId, required this.guaranteedAmount, required this.status, this.approvedAt,
    required this.createdAt, this.guarantorName});

  factory Guarantor.fromJson(Map<String, dynamic> j) {
    final g = j['guarantorMember'] is Map ? j['guarantorMember'] as Map : null;
    return Guarantor(
      id: _s(j['id']),
      loanId: _s(j['loanId']),
      loanNumber: _s(j['loanNumber'] ?? (j['loan'] is Map ? j['loan']['loanNumber'] : '')),
      borrowerId: _s(j['borrowerId'] ?? j['memberId']),
      guarantorId: _s(j['guarantorId'] ?? j['guarantorMemberId'] ?? g?['id']),
      guaranteedAmount: _i(j['guaranteedAmount'] ?? j['amountGuaranteed']),
      status: _s(j['status'], 'pending'),
      approvedAt: _dateStrN(j['approvedAt'] ?? j['verifiedAt']),
      createdAt: _dateStr(j['createdAt']),
      guarantorName: _sn(g?['fullName']),
    );
  }
}

class Project {
  final String id, name, description, type, status, manager, startDate, endDate;
  final int capitalRequired, capitalRaised, expectedProfit, actualProfit, participantCount;
  Project({required this.id, required this.name, required this.description, required this.type,
    required this.capitalRequired, required this.capitalRaised, required this.expectedProfit,
    required this.actualProfit, required this.startDate, required this.endDate, required this.status,
    required this.manager, required this.participantCount});

  factory Project.fromJson(Map<String, dynamic> j) => Project(
        id: _s(j['id']),
        name: _s(j['name']),
        description: _s(j['description']),
        type: _s(j['type'], 'monthly'),
        capitalRequired: _i(j['capitalRequired']),
        capitalRaised: _i(j['capitalRaised']),
        expectedProfit: _i(j['expectedProfit']),
        actualProfit: _i(j['actualProfit']),
        startDate: _dateStr(j['startDate']),
        endDate: _dateStr(j['endDate']),
        status: _s(j['status'], 'active'),
        manager: _s(j['manager'] ?? j['managerName']),
        participantCount: _i(j['participantCount'] ?? j['investorCount']),
      );
}

class ProjectInvestment {
  final String id, projectId, memberId, status, investedAt;
  final int amount, profitShare;
  ProjectInvestment({required this.id, required this.projectId, required this.memberId, required this.amount,
    required this.profitShare, required this.status, required this.investedAt});

  factory ProjectInvestment.fromJson(Map<String, dynamic> j, {String memberId = ''}) => ProjectInvestment(
        id: _s(j['id']),
        projectId: _s(j['projectId'] ?? (j['project'] is Map ? j['project']['id'] : '')),
        memberId: _s(j['memberId'], memberId),
        amount: _i(j['amount']),
        profitShare: _i(j['profitShare']),
        status: _s(j['status'], 'active'),
        investedAt: _dateStr(j['investedAt']),
      );
}

class InsuranceAccount {
  final String id, memberId, planName, status, startDate, endDate;
  final int monthlyContribution, coverageAmount, totalContributed;
  InsuranceAccount({required this.id, required this.memberId, required this.planName,
    required this.monthlyContribution, required this.coverageAmount, required this.startDate,
    required this.endDate, required this.status, required this.totalContributed});

  factory InsuranceAccount.fromJson(Map<String, dynamic> j, {String memberId = ''}) => InsuranceAccount(
        id: _s(j['id']),
        memberId: _s(j['memberId'], memberId),
        planName: _s(j['planName'], 'Standard Plan'),
        monthlyContribution: _i(j['monthlyContribution']),
        coverageAmount: _i(j['coverageAmount']),
        startDate: _dateStr(j['startDate']),
        endDate: _dateStr(j['endDate']),
        status: _s(j['status'], 'active'),
        totalContributed: _i(j['totalContributed']),
      );
}

class InsuranceContribution {
  final String id, accountId, memberId, period, reference, date;
  final int amount;
  InsuranceContribution({required this.id, required this.accountId, required this.memberId,
    required this.amount, required this.period, required this.reference, required this.date});

  factory InsuranceContribution.fromJson(Map<String, dynamic> j, {String accountId = '', String memberId = ''}) =>
      InsuranceContribution(
        id: _s(j['id']),
        accountId: _s(j['accountId'], accountId),
        memberId: _s(j['memberId'], memberId),
        amount: _i(j['amount']),
        period: _s(j['period']),
        reference: _s(j['reference']),
        date: _dateStr(j['date'] ?? j['paidOn']),
      );
}

class InsuranceClaim {
  final String id, claimNumber, memberId, claimType, description, status, submittedAt;
  final String? paidAt;
  final int amountRequested, amountApproved;
  InsuranceClaim({required this.id, required this.claimNumber, required this.memberId, required this.claimType,
    required this.description, required this.amountRequested, required this.amountApproved, required this.status,
    required this.submittedAt, this.paidAt});

  factory InsuranceClaim.fromJson(Map<String, dynamic> j, {String memberId = ''}) => InsuranceClaim(
        id: _s(j['id']),
        claimNumber: _s(j['claimNumber']),
        memberId: _s(j['memberId'], memberId),
        claimType: _s(j['claimType']),
        description: _s(j['description']),
        amountRequested: _i(j['amountRequested']),
        amountApproved: _i(j['amountApproved']),
        status: _s(j['status'], 'submitted'),
        submittedAt: _dateStr(j['submittedAt']),
        paidAt: _dateStrN(j['paidAt']),
      );
}

class Payment {
  final String id, memberId, provider, method, externalRef, internalRef, status, purpose, paidAt;
  final String? verifiedAt, memberName;
  final int amount;
  Payment({required this.id, required this.memberId, required this.provider, required this.method,
    required this.amount, required this.externalRef, required this.internalRef, required this.status,
    required this.purpose, required this.paidAt, this.verifiedAt, this.memberName});

  factory Payment.fromJson(Map<String, dynamic> j) {
    final m = j['member'] is Map ? j['member'] as Map : null;
    return Payment(
      id: _s(j['id']),
      memberId: _s(j['memberId'] ?? m?['id']),
      provider: _s(j['provider']),
      method: _s(j['method']),
      amount: _i(j['amount']),
      externalRef: _s(j['externalRef'] ?? j['externalReference']),
      internalRef: _s(j['internalRef'] ?? j['internalReference'] ?? j['reference']),
      status: _s(j['status']),
      purpose: _s(j['purpose']),
      paidAt: _dateStr(j['paidAt']),
      verifiedAt: _dateStrN(j['verifiedAt']),
      memberName: _sn(m?['fullName']),
    );
  }
}

class Txn {
  final String id, reference, memberId, type, status, description, createdBy, createdAt;
  final int amount;
  final String? memberName;
  Txn({required this.id, required this.reference, required this.memberId, required this.type,
    required this.amount, required this.status, required this.description, required this.createdBy,
    required this.createdAt, this.memberName});

  factory Txn.fromJson(Map<String, dynamic> j) {
    final m = j['member'] is Map ? j['member'] as Map : null;
    return Txn(
      id: _s(j['id']),
      reference: _s(j['reference'] ?? j['transactionReference']),
      memberId: _s(j['memberId'] ?? m?['id']),
      type: _s(j['type']).toLowerCase(),
      amount: _i(j['amount']),
      status: _s(j['status'], 'successful'),
      description: _s(j['description']),
      createdBy: _s(j['createdBy'] ?? j['recordedBy'], 'System'),
      createdAt: _s(j['createdAt']),
      memberName: _sn(m?['fullName']),
    );
  }
}

class Account {
  final String id, code, name, type;
  final int balance;
  Account({required this.id, required this.code, required this.name, required this.type, required this.balance});

  factory Account.fromJson(Map<String, dynamic> j) => Account(
        id: _s(j['id']),
        code: _s(j['code'] ?? j['accountCode']),
        name: _s(j['name'] ?? j['accountName']),
        type: _s(j['type'] ?? j['accountType']),
        balance: _i(j['balance']),
      );
}

class JournalLine {
  final String accountCode, accountName;
  final int debit, credit;
  JournalLine({required this.accountCode, required this.accountName, required this.debit, required this.credit});

  factory JournalLine.fromJson(Map<String, dynamic> j) {
    final a = j['account'] is Map ? j['account'] as Map : null;
    return JournalLine(
      accountCode: _s(j['accountCode'] ?? a?['accountCode'] ?? a?['code']),
      accountName: _s(j['accountName'] ?? a?['accountName'] ?? a?['name']),
      debit: _i(j['debit']),
      credit: _i(j['credit']),
    );
  }
}

class JournalEntry {
  final String id, reference, description, entryDate, postedBy, transactionRef;
  final List<JournalLine> lines;
  JournalEntry({required this.id, required this.reference, required this.description, required this.entryDate,
    required this.postedBy, required this.transactionRef, required this.lines});

  factory JournalEntry.fromJson(Map<String, dynamic> j) => JournalEntry(
        id: _s(j['id']),
        reference: _s(j['reference'] ?? j['entryNumber']),
        description: _s(j['description'] ?? j['narration']),
        entryDate: _dateStr(j['entryDate']),
        postedBy: _s(j['postedBy'] ?? j['createdBy'], 'System'),
        transactionRef: _s(j['transactionRef'] ?? j['transactionReference']),
        lines: ((j['lines'] ?? j['journalLines']) as List? ?? [])
            .map((e) => JournalLine.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}

class ProfitDistribution {
  final String id, periodStart, periodEnd, basis, status;
  final String? distributionDate;
  final int totalProfit, reservedAmount, distributableProfit;
  ProfitDistribution({required this.id, required this.periodStart, required this.periodEnd,
    required this.totalProfit, required this.reservedAmount, required this.distributableProfit,
    required this.basis, required this.status, this.distributionDate});

  factory ProfitDistribution.fromJson(Map<String, dynamic> j) => ProfitDistribution(
        id: _s(j['id']),
        periodStart: _dateStr(j['periodStart']),
        periodEnd: _dateStr(j['periodEnd']),
        totalProfit: _i(j['totalProfit']),
        reservedAmount: _i(j['reservedAmount'] ?? j['reserveAmount']),
        distributableProfit: _i(j['distributableProfit']),
        basis: _s(j['basis'], 'shares'),
        status: _s(j['status'], 'calculated'),
        distributionDate: _dateStrN(j['distributionDate']),
      );
}

class AppNotification {
  final String id, title, message, type, channel, createdAt;
  bool read;
  AppNotification({required this.id, required this.title, required this.message, required this.type,
    required this.channel, required this.read, required this.createdAt});

  factory AppNotification.fromJson(Map<String, dynamic> j) => AppNotification(
        id: _s(j['id']),
        title: _s(j['title']),
        message: _s(j['message'] ?? j['body']),
        type: _s(j['type'], 'info'),
        channel: _s(j['channel'], 'in_app'),
        read: j['read'] == true || j['readAt'] != null,
        createdAt: _s(j['createdAt']),
      );
}

class AuditLog {
  final String id, user, action, entity, entityId, ipAddress, createdAt;
  final String? oldValue, newValue;
  AuditLog({required this.id, required this.user, required this.action, required this.entity,
    required this.entityId, this.oldValue, this.newValue, required this.ipAddress, required this.createdAt});

  factory AuditLog.fromJson(Map<String, dynamic> j) {
    final u = j['user'] is Map ? j['user'] as Map : null;
    return AuditLog(
      id: _s(j['id']),
      user: _s(u?['email'] ?? u?['name'] ?? j['user'] ?? j['userEmail'], 'System'),
      action: _s(j['action']),
      entity: _s(j['entity'] ?? j['entityType'] ?? j['auditableType']),
      entityId: _s(j['entityId'] ?? j['auditableId']),
      oldValue: _sn(j['oldValue'] ?? j['oldValues']),
      newValue: _sn(j['newValue'] ?? j['newValues']),
      ipAddress: _s(j['ipAddress'], '—'),
      createdAt: _s(j['createdAt']),
    );
  }
}

class StaffUser {
  final String id, name, email, phone, status;
  final Role role;
  final String? lastLoginAt;
  StaffUser({required this.id, required this.name, required this.email, required this.phone,
    required this.role, required this.status, this.lastLoginAt});

  factory StaffUser.fromJson(Map<String, dynamic> j) => StaffUser(
        id: _s(j['id']),
        name: _s(j['name']),
        email: _s(j['email']),
        phone: _s(j['phone']),
        role: roleFromKey(_sn(j['role']) ?? (j['roles'] is List && (j['roles'] as List).isNotEmpty ? j['roles'][0] : null)),
        status: _s(j['status'], 'active'),
        lastLoginAt: _sn(j['lastLoginAt']),
      );
}
