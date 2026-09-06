import { apiDelete, apiGet, apiList, apiPost, apiPatch, apiPut } from '@/lib/api'

/* ---------------------------- organization ---------------------------- */
export const getOrganization = () => apiGet('/organization')
export const updateOrganization = (body: unknown) => apiPut('/organization', body)

/* ------------------------------ dashboard ------------------------------ */
export const getDashboard = () => apiGet('/dashboard')

/* ------------------------------- members ------------------------------- */
export const listMembers = (query?: Record<string, string | number | undefined>) =>
  apiList('/members', query)
export const getMember = (id: string) => apiGet(`/members/${id}`)
export const createMember = (body: unknown) => apiPost('/members', body)
export const updateMember = (id: string, body: unknown) => apiPatch(`/members/${id}`, body)
export const deleteMember = (id: string) => apiDelete(`/members/${id}`)

/* -------------------------------- shares ------------------------------- */
export const listShares = (query?: Record<string, string | number | undefined>) =>
  apiList('/shares', query)
export const sharesSummary = () => apiGet('/shares/summary')
export const recordSharePurchase = (body: unknown) => apiPost('/shares', body)
export const updateShare = (id: string, body: unknown) => apiPatch(`/shares/${id}`, body)
export const deleteShare = (id: string) => apiDelete(`/shares/${id}`)

/* -------------------------------- savings ------------------------------ */
export const listSavings = (query?: Record<string, string | number | undefined>) =>
  apiList('/savings', query)
export const savingsSummary = () => apiGet('/savings/summary')
export const getSavingsAccount = (id: string) => apiGet(`/savings/${id}`)
export const deposit = (body: unknown) => apiPost('/savings/deposit', body)
export const withdraw = (body: unknown) => apiPost('/savings/withdraw', body)

/* --------------------------------- loans ------------------------------- */
export const listLoans = (query?: Record<string, string | number | undefined>) =>
  apiList('/loans', query)
export const loansSummary = () => apiGet('/loans/summary')
export const getLoan = (id: string) => apiGet(`/loans/${id}`)
export const loanQuote = (body: unknown) => apiPost('/loans/quote', body)
export const loanEligibility = (body: unknown) => apiPost('/loans/eligibility', body)
export const applyLoan = (body: unknown) => apiPost('/loans/apply', body)
export const approveLoan = (id: string) => apiPost(`/loans/${id}/approve`)
export const rejectLoan = (id: string, reason?: string) => apiPost(`/loans/${id}/reject`, { reason })
export const disburseLoan = (id: string) => apiPost(`/loans/${id}/disburse`)
export const repayLoan = (id: string, body: unknown) => apiPost(`/loans/${id}/repay`, body)
export const cancelLoan = (id: string) => apiPost(`/loans/${id}/cancel`)

export const listLoanProducts = () => apiGet('/loan-products')
export const createLoanProduct = (body: unknown) => apiPost('/loan-products', body)
export const updateLoanProduct = (id: string, body: unknown) => apiPut(`/loan-products/${id}`, body)
export const deleteLoanProduct = (id: string) => apiDelete(`/loan-products/${id}`)

/* ------------------------------ guarantors ----------------------------- */
export const listGuarantors = (query?: Record<string, string | number | undefined>) =>
  apiList('/guarantors', query)
export const verifyGuarantor = (id: string) => apiPost(`/guarantors/${id}/verify`)
export const releaseGuarantor = (id: string) => apiDelete(`/guarantors/${id}`)

/* ------------------------------- projects ------------------------------ */
export const listProjects = () => apiGet('/projects')
export const getProject = (id: string) => apiGet(`/projects/${id}`)
export const createProject = (body: unknown) => apiPost('/projects', body)
export const updateProject = (id: string, body: unknown) => apiPatch(`/projects/${id}`, body)
export const deleteProject = (id: string) => apiDelete(`/projects/${id}`)
export const investProject = (id: string, body: unknown) => apiPost(`/projects/${id}/invest`, body)

/* ------------------------------ insurance ------------------------------ */
export const listInsuranceAccounts = (query?: Record<string, string | number | undefined>) =>
  apiList('/insurance/accounts', query)
export const listInsuranceClaims = (query?: Record<string, string | number | undefined>) =>
  apiList('/insurance/claims', query)
export const insuranceSummary = () => apiGet('/insurance/summary')
export const recordContribution = (body: unknown) => apiPost('/insurance/contributions', body)
export const fileClaim = (body: unknown) => apiPost('/insurance/claims', body)
export const decideClaim = (id: string, body: unknown) => apiPost(`/insurance/claims/${id}/decide`, body)
export const updateInsuranceAccount = (id: string, body: unknown) => apiPatch(`/insurance/accounts/${id}`, body)
export const cancelInsuranceAccount = (id: string) => apiDelete(`/insurance/accounts/${id}`)
export const cancelInsuranceClaim = (id: string) => apiDelete(`/insurance/claims/${id}`)

/* ------------------------------- payments ------------------------------ */
export const listPayments = (query?: Record<string, string | number | undefined>) =>
  apiList('/payments', query)
export const verifyPayment = (id: string) => apiPost(`/payments/${id}/verify`)
export const reversePayment = (id: string, reason?: string) => apiPost(`/payments/${id}/reverse`, { reason })

/* ----------------------------- transactions --------------------------- */
export const listTransactions = (query?: Record<string, string | number | undefined>) =>
  apiList('/transactions', query)
export const reverseTransaction = (id: string, reason?: string) => apiPost(`/transactions/${id}/reverse`, { reason })

/* ------------------------------ accounting ---------------------------- */
export const listAccounts = () => apiGet('/accounting/accounts')
export const createAccount = (body: unknown) => apiPost('/accounting/accounts', body)
export const updateAccount = (id: string, body: unknown) => apiPatch(`/accounting/accounts/${id}`, body)
export const deleteAccount = (id: string) => apiDelete(`/accounting/accounts/${id}`)
export const listJournal = (query?: Record<string, string | number | undefined>) =>
  apiList('/accounting/journal', query)
export const getJournalEntry = (id: string) => apiGet(`/accounting/journal/${id}`)
export const reverseJournal = (id: string) => apiPost(`/accounting/journal/${id}/reverse`)
export const trialBalance = () => apiGet('/accounting/trial-balance')

/* -------------------------------- profit ------------------------------ */
export const listProfitDistributions = () => apiGet('/profit-distributions')
export const getProfitDistribution = (id: string) => apiGet(`/profit-distributions/${id}`)
export const deleteProfitDistribution = (id: string) => apiDelete(`/profit-distributions/${id}`)

/* ---------------------------- notifications --------------------------- */
export const listNotifications = (query?: Record<string, string | number | undefined>) =>
  apiList('/notifications', query)
export const markAllNotificationsRead = () => apiPost('/notifications/read-all')
export const sendAnnouncement = (body: unknown) => apiPost('/notifications/announce', body)
export const deleteNotification = (id: string) => apiDelete(`/notifications/${id}`)

/* ------------------------------- settings ----------------------------- */
export const getSettings = () => apiGet('/settings')
export const updateSettings = (body: unknown) => apiPut('/settings', body)

/* --------------------------- users & roles --------------------------- */
export const listUsers = () => apiGet('/users')
export const createUser = (body: unknown) => apiPost('/users', body)
export const updateUser = (id: string, body: unknown) => apiPatch(`/users/${id}`, body)
export const deleteUser = (id: string) => apiDelete(`/users/${id}`)
export const listRoles = () => apiGet('/roles')
export const updateRole = (name: string, permissions: string[]) => apiPut(`/roles/${name}`, { permissions })

/* -------------------------------- audit ------------------------------- */
export const listAuditLogs = (query?: Record<string, string | number | undefined>) =>
  apiList('/audit-logs', query)

/* ---------------------------- member portal --------------------------- */
export const mePosition = () => apiGet('/me/position')
export const meShares = () => apiGet('/me/shares')
export const meSavings = () => apiGet('/me/savings')
export const meLoans = () => apiGet('/me/loans')
export const meLoan = (id: string) => apiGet(`/me/loans/${id}`)
export const meRepayments = () => apiGet('/me/repayments')
export const meProjects = () => apiGet('/me/projects')
export const meInsurance = () => apiGet('/me/insurance')
export const meTransactions = () => apiGet('/me/transactions')
export const meNotifications = () => apiList('/me/notifications')
export const meUpdateProfile = (body: unknown) => apiPatch('/me/profile', body)
