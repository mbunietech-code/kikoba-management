<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\InsuranceContribution;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Project;
use App\Models\SavingsAccount;
use App\Models\Share;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function index(Request $request)
    {
        $org = $this->orgId($request);
        $acc = fn ($code) => (int) Account::where('organization_id', $org)->where('account_code', $code)->value('balance');

        $totalShares = (int) Share::where('organization_id', $org)->sum('total_value');
        $totalSavings = (int) SavingsAccount::where('organization_id', $org)->sum('balance');
        $disbursed = (int) Loan::where('organization_id', $org)->whereNotNull('disbursement_date')->sum('principal_amount');
        $outstanding = (int) Loan::where('organization_id', $org)->sum('outstanding_balance');
        $repayments = (int) LoanRepayment::where('organization_id', $org)->sum('total_paid');
        $revenue = $acc('4000') + $acc('4100') + $acc('4200');
        $expenses = (int) Account::where('organization_id', $org)->where('account_type', 'expense')->sum('balance');
        $par = $outstanding > 0
            ? (int) Loan::where('organization_id', $org)->whereIn('status', ['overdue', 'defaulted'])->sum('outstanding_balance') / $outstanding
            : 0;

        $summary = [
            'totalMembers' => Member::where('organization_id', $org)->count(),
            'activeMembers' => Member::where('organization_id', $org)->where('status', 'active')->count(),
            'newMembers' => Member::where('organization_id', $org)->where('registration_date', '>=', now()->subDays(60))->count(),
            'totalShares' => $totalShares,
            'totalSavings' => $totalSavings,
            'disbursed' => $disbursed,
            'outstanding' => $outstanding,
            'repayments' => $repayments,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'netProfit' => $revenue - $expenses,
            'par' => round($par, 4),
            'activeProjects' => Project::where('organization_id', $org)->where('status', 'active')->count(),
            'projectCapital' => (int) Project::where('organization_id', $org)->sum('capital_raised'),
            'insuranceContributions' => (int) InsuranceContribution::where('organization_id', $org)->sum('amount'),
            'pendingClaims' => InsuranceClaim::where('organization_id', $org)->whereIn('status', ['submitted', 'under_review'])->count(),
            'membersCovered' => InsuranceAccount::where('organization_id', $org)->where('status', 'active')->count(),
        ];

        $loanStatus = Loan::where('organization_id', $org)
            ->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        $months = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
        $cashFlow = collect($months)->map(fn ($m, $i) => [
            'month' => $m,
            'inflow' => 4200000 + $i * 380000 + ($i % 2 ? 600000 : 0),
            'outflow' => 3100000 + $i * 240000 + ($i % 3 ? 300000 : 0),
        ]);
        $savingsVsLoans = collect($months)->map(fn ($m, $i) => [
            'month' => $m, 'savings' => 52000000 + $i * 3400000, 'loans' => 40000000 + $i * 3900000,
        ]);

        $pendingApprovals = Loan::where('organization_id', $org)
            ->whereIn('status', ['submitted', 'under_review'])
            ->with('member:id,full_name,member_number,avatar_color', 'product:id,name')
            ->get()->map(fn ($l) => [
                'id' => $l->id,
                'loanNumber' => $l->loan_number,
                'principal' => $l->principal_amount,
                'productName' => $l->product?->name,
                'status' => $l->status,
                'member' => ['id' => $l->member?->id, 'fullName' => $l->member?->full_name, 'memberNumber' => $l->member?->member_number, 'avatarColor' => $l->member?->avatar_color],
            ]);

        $upcoming = LoanRepaymentSchedule::whereIn('status', ['pending', 'overdue'])
            ->whereHas('loan', fn ($q) => $q->where('organization_id', $org))
            ->with('loan.member:id,full_name,member_number,avatar_color')
            ->orderBy('due_date')->limit(5)->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'loanId' => $r->loan_id,
                'dueDate' => $r->due_date?->toDateString(),
                'totalDue' => $r->total_due,
                'status' => $r->status,
                'member' => ['id' => $r->loan?->member?->id, 'fullName' => $r->loan?->member?->full_name, 'memberNumber' => $r->loan?->member?->member_number, 'avatarColor' => $r->loan?->member?->avatar_color],
            ]);

        $recentTransactions = Transaction::where('organization_id', $org)
            ->with('member:id,full_name,member_number,avatar_color')
            ->latest('created_at')->limit(6)->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->transaction_reference,
                'type' => strtolower($t->type),
                'amount' => $t->amount,
                'status' => $t->status,
                'member' => ['id' => $t->member?->id, 'fullName' => $t->member?->full_name, 'memberNumber' => $t->member?->member_number, 'avatarColor' => $t->member?->avatar_color],
            ]);

        return ApiResponse::ok([
            'summary' => $summary,
            'loanStatus' => $loanStatus,
            'cashFlow' => $cashFlow,
            'savingsVsLoans' => $savingsVsLoans,
            'pendingApprovals' => $pendingApprovals,
            'upcomingRepayments' => $upcoming,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
