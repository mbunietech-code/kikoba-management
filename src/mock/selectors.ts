import {
  accounts, activeMembers, insuranceAccounts, insuranceClaims, insuranceContributions,
  loanRepayments, loans, members, projectInvestments, projects, savingsAccounts, shares,
  transactions,
} from './data'

export const sum = (arr: number[]) => arr.reduce((a, b) => a + b, 0)

export function groupSummary() {
  const totalShares = sum(shares.map((s) => s.totalValue))
  const totalSavings = sum(savingsAccounts.map((a) => a.balance))
  const disbursed = sum(loans.filter((l) => l.disbursementDate).map((l) => l.principal))
  const outstanding = sum(loans.map((l) => l.outstanding))
  const repayments = sum(loanRepayments.map((r) => r.totalPaid))
  const interestIncome = accounts.find((a) => a.code === '4000')?.balance ?? 0
  const projectIncome = accounts.find((a) => a.code === '4100')?.balance ?? 0
  const feesIncome = accounts.find((a) => a.code === '4200')?.balance ?? 0
  const expenses = sum(accounts.filter((a) => a.type === 'expense').map((a) => a.balance))
  const revenue = interestIncome + projectIncome + feesIncome
  const netProfit = revenue - expenses
  const parLoans = loans.filter((l) => l.status === 'overdue' || l.status === 'defaulted')
  const par = outstanding ? sum(parLoans.map((l) => l.outstanding)) / outstanding : 0

  return {
    totalMembers: members.length,
    activeMembers: activeMembers.length,
    newMembers: members.filter((m) => Date.now() - +new Date(m.registrationDate) < 60 * 864e5).length,
    totalShares,
    totalSavings,
    disbursed,
    outstanding,
    repayments,
    revenue,
    expenses,
    netProfit,
    par,
    activeProjects: projects.filter((p) => p.status === 'active').length,
    projectCapital: sum(projects.map((p) => p.capitalRaised)),
    insuranceContributions: sum(insuranceContributions.map((c) => c.amount)),
    pendingClaims: insuranceClaims.filter((c) => ['submitted', 'under_review'].includes(c.status)).length,
    membersCovered: insuranceAccounts.filter((a) => a.status === 'active').length,
  }
}

export function cashFlowSeries() {
  const months = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
  return months.map((m, i) => ({
    month: m,
    inflow: 4_200_000 + i * 380_000 + (i % 2 ? 600_000 : 0),
    outflow: 3_100_000 + i * 240_000 + (i % 3 ? 300_000 : 0),
  }))
}

export function savingsVsLoansSeries() {
  const months = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
  return months.map((m, i) => ({
    month: m,
    savings: 52_000_000 + i * 3_400_000,
    loans: 40_000_000 + i * 3_900_000,
  }))
}

export function loanStatusBreakdown() {
  const map = new Map<string, number>()
  loans.forEach((l) => map.set(l.status, (map.get(l.status) ?? 0) + 1))
  return [...map.entries()].map(([status, count]) => ({ status, count }))
}

export function contributionTrend() {
  const months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
  return months.map((m, i) => ({ month: m, amount: 620_000 + i * 45_000 + (i % 2 ? 40_000 : 0) }))
}

/* -------- member-scoped -------- */
export function memberPosition(memberId: string) {
  const memberShares = shares.filter((s) => s.memberId === memberId)
  const savingsAcc = savingsAccounts.find((a) => a.memberId === memberId)
  const memberLoans = loans.filter((l) => l.memberId === memberId)
  const activeLoan = memberLoans.find((l) => ['active', 'overdue', 'disbursed'].includes(l.status))
  const invest = projectInvestments.filter((p) => p.memberId === memberId)
  const insurance = insuranceAccounts.find((a) => a.memberId === memberId)

  return {
    shareValue: sum(memberShares.map((s) => s.totalValue)),
    shareQty: sum(memberShares.map((s) => s.quantity)),
    savingsBalance: savingsAcc?.balance ?? 0,
    savingsAccountId: savingsAcc?.id,
    activeLoan,
    loanOutstanding: sum(memberLoans.map((l) => l.outstanding)),
    projectInvestment: sum(invest.map((p) => p.amount)),
    projectReturn: sum(invest.map((p) => p.amount + p.profitShare)),
    profit: sum(invest.map((p) => p.profitShare)) + Math.round((sum(memberShares.map((s) => s.totalValue)) / 1_000_000) * 42_000),
    insurance,
    loans: memberLoans,
    investments: invest,
  }
}

export function memberTransactions(memberId: string) {
  return transactions.filter((t) => t.memberId === memberId)
}
