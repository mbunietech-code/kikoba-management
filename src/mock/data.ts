import type {
  Account, AppNotification, AuditLog, Guarantor, InsuranceAccount, InsuranceClaim,
  InsuranceContribution, JournalEntry, Loan, LoanProduct, LoanRepayment, Member, Payment,
  Project, ProjectInvestment, ProfitDistribution, RepaymentScheduleRow, SavingsAccount,
  SavingsTransaction, Share, StaffUser, Transaction,
} from '@/types'

/* ----------------------------- seeded RNG ----------------------------- */
let seed = 20260905
function rnd() {
  seed = (seed * 1664525 + 1013904223) % 4294967296
  return seed / 4294967296
}
const pick = <T,>(arr: readonly T[]): T => arr[Math.floor(rnd() * arr.length)]
const int = (min: number, max: number) => Math.floor(rnd() * (max - min + 1)) + min
const round = (n: number, to = 1000) => Math.round(n / to) * to
function daysAgo(d: number) {
  const dt = new Date('2026-09-05T09:00:00')
  dt.setDate(dt.getDate() - d)
  return dt.toISOString()
}
function daysAhead(d: number) {
  return daysAgo(-d)
}

const FIRST = ['Amina', 'Baraka', 'Neema', 'Juma', 'Fatuma', 'Hamisi', 'Zainabu', 'Rajabu', 'Grace', 'Emmanuel', 'Mwajuma', 'Said', 'Halima', 'Frank', 'Rehema', 'Deo', 'Anna', 'Kelvin', 'Upendo', 'Ibrahim', 'Joyce', 'Salum', 'Doreen', 'Michael']
const LAST = ['Mushi', 'Kileo', 'Mrema', 'Shirima', 'Massawe', 'Kimaro', 'Lyimo', 'Moshi', 'Temba', 'Swai', 'Nkya', 'Macha', 'Urio', 'Kessy', 'Mollel', 'Sanga', 'Mbwana', 'Chuwa', 'Minja', 'Kweka']
const AVATAR = ['#115e59', '#2563eb', '#16a34a', '#0f766e', '#1d4ed8', '#15803d', '#0d9488', '#7c3aed', '#c2410c', '#be123c']

/* ----------------------------- members ----------------------------- */
export const members: Member[] = Array.from({ length: 24 }).map((_, i) => {
  const fullName = `${pick(FIRST)} ${pick(LAST)}`
  const status = i < 19 ? 'active' : pick(['pending', 'suspended', 'inactive', 'deceased'] as const)
  return {
    id: `m${i + 1}`,
    memberNumber: `MBR-${String(i + 1).padStart(6, '0')}`,
    fullName,
    phone: `+2557${int(10, 89)} ${int(100, 999)} ${int(100, 999)}`,
    email: `${fullName.toLowerCase().replace(/[^a-z]/g, '.')}@mfano.co.tz`,
    gender: pick(['male', 'female'] as const),
    dateOfBirth: daysAgo(int(7000, 20000)).slice(0, 10),
    address: `${pick(['Njiro', 'Kilombero', 'Sakina', 'Moshono', 'Sombetini', 'Kaloleni'])}, ${pick(['Arusha', 'Moshi', 'Dar es Salaam'])}`,
    nextOfKin: `${pick(FIRST)} ${pick(LAST)}`,
    nextOfKinPhone: `+2556${int(10, 89)} ${int(100, 999)} ${int(100, 999)}`,
    registrationDate: daysAgo(int(30, 900)).slice(0, 10),
    status,
    avatarColor: AVATAR[i % AVATAR.length],
  }
})

export const activeMembers = members.filter((m) => m.status === 'active')

/* ----------------------------- shares ----------------------------- */
const SHARE_PRICE = 10_000
export const shares: Share[] = []
members.forEach((m, i) => {
  const buys = m.status === 'active' ? int(1, 4) : int(0, 1)
  for (let b = 0; b < buys; b++) {
    const quantity = int(5, 60)
    shares.push({
      id: `sh${i}-${b}`,
      memberId: m.id,
      quantity,
      pricePerShare: SHARE_PRICE,
      totalValue: quantity * SHARE_PRICE,
      purchasedAt: daysAgo(int(10, 800)).slice(0, 10),
      transactionRef: `TXN-2026-${String(shares.length + 1).padStart(6, '0')}`,
      status: 'confirmed',
    })
  }
})

/* ----------------------------- savings ----------------------------- */
export const savingsAccounts: SavingsAccount[] = members.map((m, i) => ({
  id: `sa${i + 1}`,
  memberId: m.id,
  accountNumber: `SAV-${String(i + 1).padStart(6, '0')}`,
  balance: 0,
  status: m.status === 'active' ? 'active' : 'dormant',
  openedAt: m.registrationDate,
}))

export const savingsTransactions: SavingsTransaction[] = []
savingsAccounts.forEach((acc) => {
  let bal = 0
  const n = int(6, 16)
  for (let t = 0; t < n; t++) {
    const isWithdrawal = rnd() < 0.22 && bal > 100_000
    const amount = isWithdrawal ? round(int(20, 200) * 1000) : round(int(30, 350) * 1000)
    const before = bal
    bal = isWithdrawal ? bal - amount : bal + amount
    savingsTransactions.push({
      id: `st${savingsTransactions.length + 1}`,
      accountId: acc.id,
      memberId: acc.memberId,
      type: isWithdrawal ? 'withdrawal' : 'deposit',
      amount,
      balanceBefore: before,
      balanceAfter: bal,
      reference: `TXN-2026-${String(3000 + savingsTransactions.length).padStart(6, '0')}`,
      date: daysAgo(int(1, 500)).slice(0, 10),
    })
  }
  acc.balance = bal
})
savingsTransactions.sort((a, b) => +new Date(b.date) - +new Date(a.date))

/* ----------------------------- loan products ----------------------------- */
export const loanProducts: LoanProduct[] = [
  { id: 'lp1', name: 'Normal Loan', description: 'Standard member loan against savings.', minAmount: 100_000, maxAmount: 5_000_000, interestRate: 10, interestMethod: 'reducing', repaymentPeriod: 6, repaymentFrequency: 'monthly', processingFee: 1, insuranceFee: 1, penaltyRate: 5, minSavings: 100_000, minShares: 10, requiredGuarantors: 2, status: 'active' },
  { id: 'lp2', name: 'Emergency Loan', description: 'Fast, short-term loan for urgent needs.', minAmount: 50_000, maxAmount: 1_000_000, interestRate: 8, interestMethod: 'flat', repaymentPeriod: 3, repaymentFrequency: 'monthly', processingFee: 1.5, insuranceFee: 1, penaltyRate: 5, minSavings: 50_000, minShares: 5, requiredGuarantors: 1, status: 'active' },
  { id: 'lp3', name: 'Project Loan', description: 'Financing for member income-generating projects.', minAmount: 500_000, maxAmount: 10_000_000, interestRate: 12, interestMethod: 'reducing', repaymentPeriod: 12, repaymentFrequency: 'monthly', processingFee: 2, insuranceFee: 1.5, penaltyRate: 5, minSavings: 300_000, minShares: 30, requiredGuarantors: 3, status: 'active' },
  { id: 'lp4', name: 'Large Loan', description: 'High-value loan with extended repayment.', minAmount: 5_000_000, maxAmount: 30_000_000, interestRate: 14, interestMethod: 'reducing', repaymentPeriod: 24, repaymentFrequency: 'monthly', processingFee: 2.5, insuranceFee: 2, penaltyRate: 6, minSavings: 1_000_000, minShares: 100, requiredGuarantors: 3, status: 'active' },
]

/* ----------------------------- loans ----------------------------- */
const LOAN_STATUSES: Loan['status'][] = ['active', 'active', 'active', 'overdue', 'completed', 'under_review', 'submitted', 'approved', 'disbursed', 'rejected', 'defaulted']
export const loans: Loan[] = []
export const repaymentSchedules: RepaymentScheduleRow[] = []
export const loanRepayments: LoanRepayment[] = []

activeMembers.slice(0, 16).forEach((m, i) => {
  const product = pick(loanProducts)
  const principal = round(int(product.minAmount / 1000, Math.min(product.maxAmount, 8_000_000) / 1000) * 1000, 50_000)
  const interest = Math.round((principal * product.interestRate * product.repaymentPeriod) / (100 * 12))
  const fees = Math.round((principal * product.processingFee) / 100)
  const insurance = Math.round((principal * product.insuranceFee) / 100)
  const status = LOAN_STATUSES[i % LOAN_STATUSES.length]
  const total = principal + interest + fees + insurance
  const isRunningLike = ['active', 'overdue', 'completed', 'disbursed', 'defaulted'].includes(status)
  const paidRatio = status === 'completed' ? 1 : status === 'overdue' ? 0.35 : status === 'defaulted' ? 0.2 : isRunningLike ? rnd() * 0.7 + 0.1 : 0
  const amountPaid = round(total * paidRatio, 1000)
  const appDate = daysAgo(int(20, 400))
  const loanId = `ln${i + 1}`
  const loanNumber = `LN-${String(i + 1).padStart(6, '0')}`
  const disbursed = isRunningLike || status === 'approved'

  loans.push({
    id: loanId,
    loanNumber,
    memberId: m.id,
    productId: product.id,
    productName: product.name,
    principal,
    interest,
    fees,
    insurance,
    penalty: status === 'overdue' || status === 'defaulted' ? round(interest * 0.1, 1000) : 0,
    total,
    amountPaid,
    outstanding: Math.max(0, total - amountPaid),
    status,
    purpose: pick(['Business stock', 'School fees', 'Farm inputs', 'Home improvement', 'Medical', 'Equipment purchase', 'Working capital']),
    period: product.repaymentPeriod,
    frequency: product.repaymentFrequency,
    interestMethod: product.interestMethod,
    applicationDate: appDate.slice(0, 10),
    approvalDate: disbursed || status === 'under_review' ? daysAgo(int(15, 380)).slice(0, 10) : undefined,
    disbursementDate: disbursed ? daysAgo(int(10, 360)).slice(0, 10) : undefined,
    maturityDate: disbursed ? daysAhead(int(-60, 300)).slice(0, 10) : undefined,
  })

  if (disbursed) {
    const perInst = Math.round(total / product.repaymentPeriod)
    let paidLeft = amountPaid
    for (let k = 1; k <= product.repaymentPeriod; k++) {
      const totalDue = k === product.repaymentPeriod ? total - perInst * (product.repaymentPeriod - 1) : perInst
      const payToThis = Math.min(paidLeft, totalDue)
      paidLeft -= payToThis
      const due = daysAhead(int(-40, 260) + k * 30 - 120)
      const rowStatus = payToThis >= totalDue ? 'paid' : payToThis > 0 ? 'partial' : new Date(due) < new Date('2026-09-05') ? 'overdue' : 'pending'
      repaymentSchedules.push({
        id: `${loanId}-s${k}`,
        loanId,
        installment: k,
        dueDate: due.slice(0, 10),
        principalDue: Math.round((principal / product.repaymentPeriod)),
        interestDue: Math.round(interest / product.repaymentPeriod),
        feeDue: Math.round((fees + insurance) / product.repaymentPeriod),
        penaltyDue: 0,
        totalDue,
        amountPaid: payToThis,
        status: rowStatus,
        paidAt: payToThis >= totalDue ? due.slice(0, 10) : undefined,
      })
      if (payToThis > 0) {
        loanRepayments.push({
          id: `${loanId}-r${k}`,
          loanId,
          memberId: m.id,
          installment: k,
          principalPaid: Math.round(payToThis * (principal / total)),
          interestPaid: Math.round(payToThis * (interest / total)),
          feePaid: Math.round(payToThis * ((fees + insurance) / total)),
          penaltyPaid: 0,
          totalPaid: payToThis,
          date: due.slice(0, 10),
          reference: `TXN-2026-${String(5000 + loanRepayments.length).padStart(6, '0')}`,
          method: pick(['mobile_money', 'bank', 'cash']),
        })
      }
    }
  }
})

/* ----------------------------- guarantors ----------------------------- */
export const guarantors: Guarantor[] = []
loans.forEach((loan, i) => {
  const product = loanProducts.find((p) => p.id === loan.productId)!
  for (let g = 0; g < product.requiredGuarantors; g++) {
    const guar = pick(activeMembers.filter((m) => m.id !== loan.memberId))
    guarantors.push({
      id: `gr${guarantors.length + 1}`,
      loanId: loan.id,
      loanNumber: loan.loanNumber,
      borrowerId: loan.memberId,
      guarantorId: guar.id,
      guaranteedAmount: round(loan.total / product.requiredGuarantors, 10_000),
      status: ['active', 'overdue', 'completed', 'disbursed', 'defaulted'].includes(loan.status)
        ? 'approved'
        : pick(['pending', 'approved'] as const),
      approvedAt: i % 2 === 0 ? daysAgo(int(10, 300)).slice(0, 10) : undefined,
      createdAt: daysAgo(int(15, 320)).slice(0, 10),
    })
  }
})

/* ----------------------------- projects ----------------------------- */
export const projects: Project[] = [
  { id: 'pr1', name: 'Maize Bulk Trading', description: 'Buy maize at harvest, store and sell in lean season.', type: 'three_months', capitalRequired: 12_000_000, capitalRaised: 12_000_000, expectedProfit: 3_000_000, actualProfit: 3_450_000, startDate: daysAgo(200).slice(0, 10), endDate: daysAgo(20).slice(0, 10), status: 'completed', manager: 'Baraka Kileo', participantCount: 14 },
  { id: 'pr2', name: 'Poultry Unit', description: 'Broiler production cycle for local hotels.', type: 'monthly', capitalRequired: 6_000_000, capitalRaised: 4_200_000, expectedProfit: 1_500_000, actualProfit: 0, startDate: daysAgo(25).slice(0, 10), endDate: daysAhead(35).slice(0, 10), status: 'active', manager: 'Neema Mrema', participantCount: 9 },
  { id: 'pr3', name: 'Boda Boda Fleet', description: 'Five motorcycles on daily rental to riders.', type: 'long_term', capitalRequired: 20_000_000, capitalRaised: 8_500_000, expectedProfit: 9_000_000, actualProfit: 0, startDate: daysAhead(10).slice(0, 10), endDate: daysAhead(375).slice(0, 10), status: 'planned', manager: 'Juma Shirima', participantCount: 6 },
  { id: 'pr4', name: 'Hardware Shop Stock', description: 'Restock building materials for peak season.', type: 'three_months', capitalRequired: 15_000_000, capitalRaised: 15_000_000, expectedProfit: 4_000_000, actualProfit: 0, startDate: daysAgo(40).slice(0, 10), endDate: daysAhead(50).slice(0, 10), status: 'active', manager: 'Grace Massawe', participantCount: 11 },
]

export const projectInvestments: ProjectInvestment[] = []
projects.forEach((p) => {
  const investors = activeMembers.slice(0, p.participantCount)
  investors.forEach((m) => {
    const amount = round(p.capitalRaised / p.participantCount + int(-200, 300) * 1000, 50_000)
    projectInvestments.push({
      id: `pi${projectInvestments.length + 1}`,
      projectId: p.id,
      memberId: m.id,
      amount: Math.max(50_000, amount),
      profitShare: p.status === 'completed' ? Math.round(p.actualProfit / p.participantCount) : 0,
      status: p.status === 'completed' ? 'completed' : 'active',
      investedAt: daysAgo(int(20, 190)).slice(0, 10),
    })
  })
})

/* ----------------------------- insurance ----------------------------- */
const INS_CONTRIB = 20_000
export const insuranceAccounts: InsuranceAccount[] = activeMembers.map((m, i) => {
  const months = int(4, 20)
  return {
    id: `ins${i + 1}`,
    memberId: m.id,
    planName: pick(['Basic Protection', 'Family Cover', 'Standard Plan']),
    monthlyContribution: INS_CONTRIB,
    coverageAmount: pick([1_000_000, 2_000_000, 3_000_000]),
    startDate: daysAgo(months * 30).slice(0, 10),
    endDate: daysAhead(int(-15, 300)).slice(0, 10),
    status: i < activeMembers.length - 3 ? 'active' : pick(['expired', 'suspended'] as const),
    totalContributed: months * INS_CONTRIB,
  }
})

export const insuranceContributions: InsuranceContribution[] = []
insuranceAccounts.forEach((acc) => {
  const months = acc.totalContributed / INS_CONTRIB
  for (let k = 0; k < months; k++) {
    const d = new Date('2026-09-01')
    d.setMonth(d.getMonth() - k)
    insuranceContributions.push({
      id: `ic${insuranceContributions.length + 1}`,
      accountId: acc.id,
      memberId: acc.memberId,
      amount: INS_CONTRIB,
      period: d.toISOString().slice(0, 7),
      reference: `TXN-2026-${String(7000 + insuranceContributions.length).padStart(6, '0')}`,
      date: d.toISOString().slice(0, 10),
    })
  }
})

export const insuranceClaims: InsuranceClaim[] = Array.from({ length: 7 }).map((_, i) => {
  const m = pick(activeMembers)
  const requested = round(int(200, 1500) * 1000, 50_000)
  const status = pick(['submitted', 'under_review', 'approved', 'rejected', 'paid', 'paid'] as const)
  return {
    id: `cl${i + 1}`,
    claimNumber: `CLM-${String(i + 1).padStart(5, '0')}`,
    memberId: m.id,
    claimType: pick(['Medical', 'Funeral', 'Property loss', 'Disability']),
    description: pick(['Hospitalisation costs', 'Bereavement support', 'Fire damage to shop', 'Accident recovery']),
    amountRequested: requested,
    amountApproved: status === 'paid' || status === 'approved' ? round(requested * (rnd() * 0.4 + 0.6), 10_000) : 0,
    status,
    submittedAt: daysAgo(int(5, 120)).slice(0, 10),
    paidAt: status === 'paid' ? daysAgo(int(1, 40)).slice(0, 10) : undefined,
  }
})

/* ----------------------------- payments ----------------------------- */
const PROVIDERS: Record<string, string> = { mobile_money: 'M-Pesa', bank: 'CRDB Bank', card: 'Selcom', cash: 'Cash desk', manual: 'Manual entry' }
export const payments: Payment[] = Array.from({ length: 40 }).map((_, i) => {
  const m = pick(members)
  const method = pick(['mobile_money', 'mobile_money', 'bank', 'cash', 'card'] as const)
  const status = pick(['successful', 'successful', 'successful', 'pending', 'failed', 'reversed'] as const)
  return {
    id: `pay${i + 1}`,
    memberId: m.id,
    provider: PROVIDERS[method],
    method,
    amount: round(int(20, 800) * 1000, 5000),
    externalRef: `${pick(['QGH', 'RTX', 'MPX', 'BNK'])}${int(100000, 999999)}`,
    internalRef: `PMT-2026-${String(i + 1).padStart(6, '0')}`,
    status,
    purpose: pick(['Savings deposit', 'Loan repayment', 'Share purchase', 'Insurance contribution', 'Project investment']),
    paidAt: daysAgo(int(0, 90)),
    verifiedAt: status === 'successful' ? daysAgo(int(0, 90)) : undefined,
  }
})

/* ----------------------------- transactions ----------------------------- */
const TXN_TYPES = ['savings_deposit', 'savings_withdrawal', 'share_purchase', 'loan_disbursement', 'loan_repayment', 'interest_payment', 'project_investment', 'insurance_payment', 'fee', 'penalty'] as const
export const transactions: Transaction[] = Array.from({ length: 60 }).map((_, i) => {
  const m = pick(members)
  const type = pick(TXN_TYPES)
  return {
    id: `t${i + 1}`,
    reference: `TXN-2026-${String(i + 1).padStart(6, '0')}`,
    memberId: m.id,
    type,
    amount: round(int(10, 900) * 1000, 1000),
    status: pick(['successful', 'successful', 'successful', 'pending', 'reversed'] as const),
    description: type.replace(/_/g, ' '),
    createdBy: pick(['T. Mushi', 'A. Kessy', 'System', 'L. Swai']),
    createdAt: daysAgo(int(0, 120)),
  }
}).sort((a, b) => +new Date(b.createdAt) - +new Date(a.createdAt))

/* ----------------------------- accounting ----------------------------- */
export const accounts: Account[] = [
  { id: 'a1', code: '1000', name: 'Cash', type: 'asset', balance: 4_250_000 },
  { id: 'a2', code: '1010', name: 'Bank', type: 'asset', balance: 38_900_000 },
  { id: 'a3', code: '1300', name: 'Loan Receivable', type: 'asset', balance: 62_400_000 },
  { id: 'a4', code: '1400', name: 'Project Investments', type: 'asset', balance: 39_700_000 },
  { id: 'a5', code: '1100', name: 'Member Savings', type: 'liability', balance: 71_200_000 },
  { id: 'a6', code: '2100', name: 'Insurance Fund', type: 'liability', balance: 8_600_000 },
  { id: 'a7', code: '1200', name: 'Member Shares', type: 'equity', balance: 24_800_000 },
  { id: 'a8', code: '3000', name: 'Retained Earnings', type: 'equity', balance: 15_300_000 },
  { id: 'a9', code: '4000', name: 'Interest Income', type: 'revenue', balance: 9_450_000 },
  { id: 'a10', code: '4100', name: 'Project Income', type: 'revenue', balance: 3_450_000 },
  { id: 'a11', code: '4200', name: 'Fees Income', type: 'revenue', balance: 1_780_000 },
  { id: 'a12', code: '5000', name: 'Insurance Expense', type: 'expense', balance: 2_100_000 },
  { id: 'a13', code: '5100', name: 'Operating Expenses', type: 'expense', balance: 3_640_000 },
  { id: 'a14', code: '5200', name: 'Project Expenses', type: 'expense', balance: 1_200_000 },
]

export const journalEntries: JournalEntry[] = Array.from({ length: 18 }).map((_, i) => {
  const scenario = pick([
    { d: ['1000', 'Cash'], c: ['1100', 'Member Savings'], desc: 'Member savings deposit', amt: round(int(50, 300) * 1000) },
    { d: ['1010', 'Bank'], c: ['1300', 'Loan Receivable'], desc: 'Loan repayment received', amt: round(int(80, 400) * 1000) },
    { d: ['1300', 'Loan Receivable'], c: ['4000', 'Interest Income'], desc: 'Interest accrued on loans', amt: round(int(20, 120) * 1000) },
    { d: ['1000', 'Cash'], c: ['1200', 'Member Shares'], desc: 'Share purchase', amt: round(int(50, 600) * 1000) },
    { d: ['5100', 'Operating Expenses'], c: ['1010', 'Bank'], desc: 'Office running costs', amt: round(int(20, 90) * 1000) },
  ])
  const amt = scenario.amt
  return {
    id: `je${i + 1}`,
    reference: `JE-2026-${String(i + 1).padStart(5, '0')}`,
    description: scenario.desc,
    entryDate: daysAgo(int(1, 150)).slice(0, 10),
    postedBy: pick(['A. Kessy (Accountant)', 'T. Mushi (Treasurer)', 'System']),
    transactionRef: `TXN-2026-${String(int(1, 200)).padStart(6, '0')}`,
    lines: [
      { accountCode: scenario.d[0], accountName: scenario.d[1], debit: amt, credit: 0 },
      { accountCode: scenario.c[0], accountName: scenario.c[1], debit: 0, credit: amt },
    ],
  }
})

/* ----------------------------- profit distributions ----------------------------- */
export const profitDistributions: ProfitDistribution[] = [
  { id: 'pd1', periodStart: '2025-01-01', periodEnd: '2025-12-31', totalProfit: 14_200_000, reservedAmount: 2_840_000, distributableProfit: 11_360_000, basis: 'shares', status: 'distributed', distributionDate: '2026-01-20' },
  { id: 'pd2', periodStart: '2026-01-01', periodEnd: '2026-06-30', totalProfit: 7_680_000, reservedAmount: 1_536_000, distributableProfit: 6_144_000, basis: 'shares', status: 'calculated' },
]

/* ----------------------------- notifications ----------------------------- */
const NOTIF_TEMPLATES = [
  { type: 'loan_approved', title: 'Loan approved', message: 'Loan {ref} has been approved and is ready for disbursement.' },
  { type: 'payment_received', title: 'Payment received', message: 'Payment of {amt} recorded against {ref}.' },
  { type: 'payment_overdue', title: 'Repayment overdue', message: 'Installment for loan {ref} is overdue by 4 days.' },
  { type: 'savings_confirmed', title: 'Savings confirmed', message: 'Your deposit of {amt} has been confirmed.' },
  { type: 'project_completed', title: 'Project completed', message: 'Project "Maize Bulk Trading" closed with a profit of TZS 3.45M.' },
  { type: 'profit_distributed', title: 'Profit distributed', message: 'Your 2025 profit allocation has been credited.' },
  { type: 'insurance_expiry', title: 'Insurance expiring', message: 'Your insurance cover expires in 12 days.' },
]
export const notifications: AppNotification[] = Array.from({ length: 14 }).map((_, i) => {
  const t = NOTIF_TEMPLATES[i % NOTIF_TEMPLATES.length]
  return {
    id: `n${i + 1}`,
    title: t.title,
    message: t.message.replace('{ref}', `LN-${String(int(1, 16)).padStart(6, '0')}`).replace('{amt}', `TZS ${int(50, 400)},000`),
    type: t.type,
    channel: pick(['in_app', 'sms', 'email', 'push'] as const),
    read: i > 4,
    createdAt: daysAgo(i),
  }
})

/* ----------------------------- audit logs ----------------------------- */
const AUDIT_ACTIONS = [
  { action: 'APPROVE_LOAN', entity: 'Loan', old: 'Pending', neu: 'Approved' },
  { action: 'CREATE_MEMBER', entity: 'Member', old: '', neu: 'Active' },
  { action: 'RECORD_DEPOSIT', entity: 'SavingsTransaction', old: '', neu: 'Successful' },
  { action: 'UPDATE_SETTINGS', entity: 'Setting', old: '10%', neu: '12%' },
  { action: 'DISBURSE_LOAN', entity: 'Loan', old: 'Approved', neu: 'Disbursed' },
  { action: 'REVERSE_TRANSACTION', entity: 'Transaction', old: 'Successful', neu: 'Reversed' },
  { action: 'VERIFY_PAYMENT', entity: 'Payment', old: 'Pending', neu: 'Successful' },
  { action: 'REJECT_CLAIM', entity: 'InsuranceClaim', old: 'Under review', neu: 'Rejected' },
]
export const auditLogs: AuditLog[] = Array.from({ length: 40 }).map((_, i) => {
  const a = AUDIT_ACTIONS[i % AUDIT_ACTIONS.length]
  return {
    id: `al${i + 1}`,
    user: pick(['super.admin@kikoba.co.tz', 't.mushi@kikoba.co.tz', 'a.kessy@kikoba.co.tz', 'l.swai@kikoba.co.tz']),
    action: a.action,
    entity: a.entity,
    entityId: `${a.entity.slice(0, 3).toUpperCase()}-${String(int(1, 300)).padStart(6, '0')}`,
    oldValue: a.old || undefined,
    newValue: a.neu,
    ipAddress: `196.${int(0, 255)}.${int(0, 255)}.${int(1, 254)}`,
    createdAt: daysAgo(i * 0.7),
  }
})

/* ----------------------------- staff users ----------------------------- */
export const staffUsers: StaffUser[] = [
  { id: 'u1', name: 'System Owner', email: 'super.admin@kikoba.co.tz', phone: '+255755 000 001', role: 'super_admin', status: 'active', lastLoginAt: daysAgo(0) },
  { id: 'u2', name: 'Fatuma Kimaro', email: 'f.kimaro@kikoba.co.tz', phone: '+255755 000 002', role: 'admin', status: 'active', lastLoginAt: daysAgo(1) },
  { id: 'u3', name: 'Tumaini Mushi', email: 't.mushi@kikoba.co.tz', phone: '+255755 000 003', role: 'treasurer', status: 'active', lastLoginAt: daysAgo(0) },
  { id: 'u4', name: 'Anna Kessy', email: 'a.kessy@kikoba.co.tz', phone: '+255755 000 004', role: 'accountant', status: 'active', lastLoginAt: daysAgo(2) },
  { id: 'u5', name: 'Lucas Swai', email: 'l.swai@kikoba.co.tz', phone: '+255755 000 005', role: 'loan_officer', status: 'active', lastLoginAt: daysAgo(3) },
  { id: 'u6', name: 'Doreen Minja', email: 'd.minja@kikoba.co.tz', phone: '+255755 000 006', role: 'loan_officer', status: 'suspended', lastLoginAt: daysAgo(30) },
]

/* ----------------------------- helpers ----------------------------- */
export const memberById = (id: string) => members.find((m) => m.id === id)
export const memberName = (id: string) => memberById(id)?.fullName ?? 'Unknown'
export const CURRENT_MEMBER_ID = activeMembers[0].id
