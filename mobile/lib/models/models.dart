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
}

class Share {
  final String id, memberId, transactionRef, purchasedAt, status;
  final int quantity, pricePerShare, totalValue;
  Share({required this.id, required this.memberId, required this.quantity, required this.pricePerShare,
    required this.totalValue, required this.purchasedAt, required this.transactionRef, required this.status});
}

class SavingsAccount {
  final String id, memberId, accountNumber, status, openedAt;
  int balance;
  SavingsAccount({required this.id, required this.memberId, required this.accountNumber,
    required this.balance, required this.status, required this.openedAt});
}

class SavingsTxn {
  final String id, accountId, memberId, type, reference, date;
  final int amount, balanceBefore, balanceAfter;
  SavingsTxn({required this.id, required this.accountId, required this.memberId, required this.type,
    required this.amount, required this.balanceBefore, required this.balanceAfter,
    required this.reference, required this.date});
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
}

class Loan {
  final String id, loanNumber, memberId, productId, productName, status, purpose, frequency, interestMethod;
  final int principal, interest, fees, insurance, penalty, total, amountPaid, outstanding, period;
  final String applicationDate;
  final String? approvalDate, disbursementDate, maturityDate;
  Loan({required this.id, required this.loanNumber, required this.memberId, required this.productId,
    required this.productName, required this.principal, required this.interest, required this.fees,
    required this.insurance, required this.penalty, required this.total, required this.amountPaid,
    required this.outstanding, required this.status, required this.purpose, required this.period,
    required this.frequency, required this.interestMethod, required this.applicationDate,
    this.approvalDate, this.disbursementDate, this.maturityDate});
}

class ScheduleRow {
  final String id, loanId, status;
  final String dueDate;
  final String? paidAt;
  final int installment, principalDue, interestDue, feeDue, penaltyDue, totalDue, amountPaid;
  ScheduleRow({required this.id, required this.loanId, required this.installment, required this.dueDate,
    required this.principalDue, required this.interestDue, required this.feeDue, required this.penaltyDue,
    required this.totalDue, required this.amountPaid, required this.status, this.paidAt});
}

class LoanRepayment {
  final String id, loanId, memberId, date, reference, method;
  final int installment, principalPaid, interestPaid, feePaid, penaltyPaid, totalPaid;
  LoanRepayment({required this.id, required this.loanId, required this.memberId, required this.installment,
    required this.principalPaid, required this.interestPaid, required this.feePaid, required this.penaltyPaid,
    required this.totalPaid, required this.date, required this.reference, required this.method});
}

class Guarantor {
  final String id, loanId, loanNumber, borrowerId, guarantorId, status, createdAt;
  final String? approvedAt;
  final int guaranteedAmount;
  Guarantor({required this.id, required this.loanId, required this.loanNumber, required this.borrowerId,
    required this.guarantorId, required this.guaranteedAmount, required this.status, this.approvedAt,
    required this.createdAt});
}

class Project {
  final String id, name, description, type, status, manager, startDate, endDate;
  final int capitalRequired, capitalRaised, expectedProfit, actualProfit, participantCount;
  Project({required this.id, required this.name, required this.description, required this.type,
    required this.capitalRequired, required this.capitalRaised, required this.expectedProfit,
    required this.actualProfit, required this.startDate, required this.endDate, required this.status,
    required this.manager, required this.participantCount});
}

class ProjectInvestment {
  final String id, projectId, memberId, status, investedAt;
  final int amount, profitShare;
  ProjectInvestment({required this.id, required this.projectId, required this.memberId, required this.amount,
    required this.profitShare, required this.status, required this.investedAt});
}

class InsuranceAccount {
  final String id, memberId, planName, status, startDate, endDate;
  final int monthlyContribution, coverageAmount, totalContributed;
  InsuranceAccount({required this.id, required this.memberId, required this.planName,
    required this.monthlyContribution, required this.coverageAmount, required this.startDate,
    required this.endDate, required this.status, required this.totalContributed});
}

class InsuranceContribution {
  final String id, accountId, memberId, period, reference, date;
  final int amount;
  InsuranceContribution({required this.id, required this.accountId, required this.memberId,
    required this.amount, required this.period, required this.reference, required this.date});
}

class InsuranceClaim {
  final String id, claimNumber, memberId, claimType, description, status, submittedAt;
  final String? paidAt;
  final int amountRequested, amountApproved;
  InsuranceClaim({required this.id, required this.claimNumber, required this.memberId, required this.claimType,
    required this.description, required this.amountRequested, required this.amountApproved, required this.status,
    required this.submittedAt, this.paidAt});
}

class Payment {
  final String id, memberId, provider, method, externalRef, internalRef, status, purpose, paidAt;
  final String? verifiedAt;
  final int amount;
  Payment({required this.id, required this.memberId, required this.provider, required this.method,
    required this.amount, required this.externalRef, required this.internalRef, required this.status,
    required this.purpose, required this.paidAt, this.verifiedAt});
}

class Txn {
  final String id, reference, memberId, type, status, description, createdBy, createdAt;
  final int amount;
  Txn({required this.id, required this.reference, required this.memberId, required this.type,
    required this.amount, required this.status, required this.description, required this.createdBy,
    required this.createdAt});
}

class Account {
  final String id, code, name, type;
  final int balance;
  Account({required this.id, required this.code, required this.name, required this.type, required this.balance});
}

class JournalLine {
  final String accountCode, accountName;
  final int debit, credit;
  JournalLine({required this.accountCode, required this.accountName, required this.debit, required this.credit});
}

class JournalEntry {
  final String id, reference, description, entryDate, postedBy, transactionRef;
  final List<JournalLine> lines;
  JournalEntry({required this.id, required this.reference, required this.description, required this.entryDate,
    required this.postedBy, required this.transactionRef, required this.lines});
}

class ProfitDistribution {
  final String id, periodStart, periodEnd, basis, status;
  final String? distributionDate;
  final int totalProfit, reservedAmount, distributableProfit;
  ProfitDistribution({required this.id, required this.periodStart, required this.periodEnd,
    required this.totalProfit, required this.reservedAmount, required this.distributableProfit,
    required this.basis, required this.status, this.distributionDate});
}

class AppNotification {
  final String id, title, message, type, channel, createdAt;
  bool read;
  AppNotification({required this.id, required this.title, required this.message, required this.type,
    required this.channel, required this.read, required this.createdAt});
}

class AuditLog {
  final String id, user, action, entity, entityId, ipAddress, createdAt;
  final String? oldValue, newValue;
  AuditLog({required this.id, required this.user, required this.action, required this.entity,
    required this.entityId, this.oldValue, this.newValue, required this.ipAddress, required this.createdAt});
}

class StaffUser {
  final String id, name, email, phone, status;
  final Role role;
  final String? lastLoginAt;
  StaffUser({required this.id, required this.name, required this.email, required this.phone,
    required this.role, required this.status, this.lastLoginAt});
}
