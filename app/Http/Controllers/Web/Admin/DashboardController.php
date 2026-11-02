<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InsuranceContribution;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Project;
use App\Models\SavingsAccount;
use App\Models\Share;
use App\Models\Transaction;
use App\Services\DashboardMetrics;
use App\Services\LoanService;

class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics, LoanService $loans)
    {
        $org = app('kikoba.org')->id;
        $loans->markOverdue($org);
        $acc = fn ($c) => (int) Account::where('organization_id', $org)->where('account_code', $c)->value('balance');

        $totalShares = (int) Share::where('organization_id', $org)->sum('total_value');
        $totalSavings = (int) SavingsAccount::where('organization_id', $org)->sum('balance');
        $disbursed = (int) Loan::where('organization_id', $org)->whereNotNull('disbursement_date')->sum('principal_amount');
        $outstanding = (int) Loan::where('organization_id', $org)->sum('outstanding_balance');
        $repayments = (int) LoanRepayment::where('organization_id', $org)->sum('total_paid');
        $revenue = $acc('4000') + $acc('4100') + $acc('4200');
        $expenses = (int) Account::where('organization_id', $org)->where('account_type', 'expense')->sum('balance');
        $parLoans = (int) Loan::where('organization_id', $org)->whereIn('status', ['overdue', 'defaulted'])->sum('outstanding_balance');

        $summary = [
            'totalMembers' => Member::where('organization_id', $org)->count(),
            'activeMembers' => Member::where('organization_id', $org)->where('status', 'active')->count(),
            'newMembers' => Member::where('organization_id', $org)->where('registration_date', '>=', now()->startOfMonth()->toDateString())->count(),
            'totalShares' => $totalShares,
            'totalSavings' => $totalSavings,
            'disbursed' => $disbursed,
            'outstanding' => $outstanding,
            'repayments' => $repayments,
            'netProfit' => $revenue - $expenses,
            'par' => $outstanding > 0 ? $parLoans / $outstanding : 0,
            'activeProjects' => Project::where('organization_id', $org)->where('status', 'active')->count(),
            'projectCapital' => (int) Project::where('organization_id', $org)->sum('capital_raised'),
            'insuranceContributions' => (int) InsuranceContribution::where('organization_id', $org)->sum('amount'),
        ];

        $loanStatus = Loan::where('organization_id', $org)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        $cashFlow = $metrics->cashFlow($org);
        $savingsVsLoans = $metrics->savingsVsLoans($org);

        $pendingApprovals = Loan::where('organization_id', $org)
            ->whereIn('status', ['submitted', 'under_review'])
            ->with('member', 'product')->get();

        $upcoming = LoanRepaymentSchedule::whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereHas('loan', fn ($q) => $q->where('organization_id', $org))
            ->with('loan.member')->orderBy('due_date')->limit(5)->get();

        $recent = Transaction::where('organization_id', $org)->with('member')->latest('created_at')->limit(6)->get();

        return view('admin.dashboard', compact(
            'summary', 'loanStatus', 'cashFlow', 'savingsVsLoans', 'pendingApprovals', 'upcoming', 'recent'
        ));
    }
}
