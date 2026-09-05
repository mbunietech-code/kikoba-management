export type Role =
  | 'super_admin'
  | 'admin'
  | 'treasurer'
  | 'accountant'
  | 'loan_officer'
  | 'member'

export type MemberStatus = 'active' | 'pending' | 'suspended' | 'inactive' | 'deceased'
export type Gender = 'male' | 'female' | 'other'

export interface Member {
  id: string
  memberNumber: string
  fullName: string
  phone: string
  email: string
  gender: Gender
  dateOfBirth: string
  address: string
  nextOfKin: string
  nextOfKinPhone: string
  registrationDate: string
  status: MemberStatus
  avatarColor: string
}

export interface Share {
  id: string
  memberId: string
  quantity: number
  pricePerShare: number
  totalValue: number
  purchasedAt: string
  transactionRef: string
  status: 'confirmed' | 'pending'
}

export interface SavingsAccount {
  id: string
  memberId: string
  accountNumber: string
  balance: number
  status: 'active' | 'dormant' | 'closed'
  openedAt: string
}

export type SavingsTxnType = 'deposit' | 'withdrawal' | 'adjustment' | 'reversal'
export interface SavingsTransaction {
  id: string
  accountId: string
  memberId: string
  type: SavingsTxnType
  amount: number
  balanceBefore: number
  balanceAfter: number
  reference: string
  date: string
}

export type InterestMethod = 'flat' | 'reducing'
export type RepaymentFrequency = 'weekly' | 'biweekly' | 'monthly' | 'quarterly'

export interface LoanProduct {
  id: string
  name: string
  description: string
  minAmount: number
  maxAmount: number
  interestRate: number
  interestMethod: InterestMethod
  repaymentPeriod: number
  repaymentFrequency: RepaymentFrequency
  processingFee: number
  insuranceFee: number
  penaltyRate: number
  minSavings: number
  minShares: number
  requiredGuarantors: number
  status: 'active' | 'inactive'
}

export type LoanStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'approved'
  | 'rejected'
  | 'disbursed'
  | 'active'
  | 'overdue'
  | 'completed'
  | 'defaulted'
  | 'cancelled'

export interface Loan {
  id: string
  loanNumber: string
  memberId: string
  productId: string
  productName: string
  principal: number
  interest: number
  fees: number
  insurance: number
  penalty: number
  total: number
  amountPaid: number
  outstanding: number
  status: LoanStatus
  purpose: string
  period: number
  frequency: RepaymentFrequency
  interestMethod: InterestMethod
  applicationDate: string
  approvalDate?: string
  disbursementDate?: string
  maturityDate?: string
}

export type ScheduleStatus = 'pending' | 'partial' | 'paid' | 'overdue'
export interface RepaymentScheduleRow {
  id: string
  loanId: string
  installment: number
  dueDate: string
  principalDue: number
  interestDue: number
  feeDue: number
  penaltyDue: number
  totalDue: number
  amountPaid: number
  status: ScheduleStatus
  paidAt?: string
}

export interface LoanRepayment {
  id: string
  loanId: string
  memberId: string
  installment: number
  principalPaid: number
  interestPaid: number
  feePaid: number
  penaltyPaid: number
  totalPaid: number
  date: string
  reference: string
  method: PaymentMethod
}

export type GuarantorStatus = 'pending' | 'approved' | 'rejected' | 'released'
export interface Guarantor {
  id: string
  loanId: string
  loanNumber: string
  borrowerId: string
  guarantorId: string
  guaranteedAmount: number
  status: GuarantorStatus
  approvedAt?: string
  createdAt: string
}

export type ProjectType = 'monthly' | 'three_months' | 'long_term' | 'custom'
export type ProjectStatus = 'planned' | 'active' | 'completed' | 'cancelled'
export interface Project {
  id: string
  name: string
  description: string
  type: ProjectType
  capitalRequired: number
  capitalRaised: number
  expectedProfit: number
  actualProfit: number
  startDate: string
  endDate: string
  status: ProjectStatus
  manager: string
  participantCount: number
}

export interface ProjectInvestment {
  id: string
  projectId: string
  memberId: string
  amount: number
  profitShare: number
  status: 'active' | 'completed'
  investedAt: string
}

export type InsuranceStatus = 'active' | 'expired' | 'suspended' | 'cancelled'
export interface InsuranceAccount {
  id: string
  memberId: string
  planName: string
  monthlyContribution: number
  coverageAmount: number
  startDate: string
  endDate: string
  status: InsuranceStatus
  totalContributed: number
}

export interface InsuranceContribution {
  id: string
  accountId: string
  memberId: string
  amount: number
  period: string
  reference: string
  date: string
}

export type ClaimStatus =
  | 'submitted'
  | 'under_review'
  | 'approved'
  | 'rejected'
  | 'paid'
  | 'cancelled'
export interface InsuranceClaim {
  id: string
  claimNumber: string
  memberId: string
  claimType: string
  description: string
  amountRequested: number
  amountApproved: number
  status: ClaimStatus
  submittedAt: string
  paidAt?: string
}

export type PaymentMethod = 'mobile_money' | 'bank' | 'card' | 'cash' | 'manual'
export type PaymentStatus = 'pending' | 'successful' | 'failed' | 'reversed'
export interface Payment {
  id: string
  memberId: string
  provider: string
  method: PaymentMethod
  amount: number
  externalRef: string
  internalRef: string
  status: PaymentStatus
  purpose: string
  paidAt: string
  verifiedAt?: string
}

export type TxnType =
  | 'share_purchase'
  | 'savings_deposit'
  | 'savings_withdrawal'
  | 'loan_disbursement'
  | 'loan_repayment'
  | 'interest_payment'
  | 'project_investment'
  | 'project_profit'
  | 'insurance_payment'
  | 'insurance_claim'
  | 'fee'
  | 'penalty'
  | 'reversal'

export interface Transaction {
  id: string
  reference: string
  memberId: string
  type: TxnType
  amount: number
  status: PaymentStatus
  description: string
  createdBy: string
  createdAt: string
}

export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense'
export interface Account {
  id: string
  code: string
  name: string
  type: AccountType
  balance: number
}

export interface JournalLine {
  accountCode: string
  accountName: string
  debit: number
  credit: number
}
export interface JournalEntry {
  id: string
  reference: string
  description: string
  entryDate: string
  postedBy: string
  transactionRef: string
  lines: JournalLine[]
}

export type DistributionStatus = 'draft' | 'calculated' | 'approved' | 'distributed'
export interface ProfitDistribution {
  id: string
  periodStart: string
  periodEnd: string
  totalProfit: number
  reservedAmount: number
  distributableProfit: number
  basis: 'shares' | 'savings' | 'equal'
  status: DistributionStatus
  distributionDate?: string
}

export type NotificationChannel = 'push' | 'sms' | 'email' | 'whatsapp' | 'in_app'
export interface AppNotification {
  id: string
  title: string
  message: string
  type: string
  channel: NotificationChannel
  read: boolean
  createdAt: string
}

export interface AuditLog {
  id: string
  user: string
  action: string
  entity: string
  entityId: string
  oldValue?: string
  newValue?: string
  ipAddress: string
  createdAt: string
}

export interface StaffUser {
  id: string
  name: string
  email: string
  phone: string
  role: Role
  status: 'active' | 'suspended'
  lastLoginAt?: string
}

export interface Session {
  role: Role
  name: string
  memberId?: string
}
