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

class DashboardController extends Controller
{
    public function index()
    {
        $org = app('kikoba.org')->id;
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
            'newMembers' => Member::where('organization_id', $org)->where('registration_date', '>=', now()->subDays(60))->count(),
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

        $months = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
        $cashFlow = [
            'labels' => $months,
            'inflow' => collect($months)->map(fn ($m, $i) => 4_200_000 + $i * 380_000 + ($i % 2 ? 600_000 : 0))->all(),
            'outflow' => collect($months)->map(fn ($m, $i) => 3_100_000 + $i * 240_000 + ($i % 3 ? 300_000 : 0))->all(),
        ];
        $savingsVsLoans = [
            'labels' => $months,
            'savings' => collect($months)->map(fn ($m, $i) => 52_000_000 + $i * 3_400_000)->all(),
            'loans' => collect($months)->map(fn ($m, $i) => 40_000_000 + $i * 3_900_000)->all(),
        ];

        $pendingApprovals = Loan::where('organization_id', $org)
            ->whereIn('status', ['submitted', 'under_review'])
            ->with('member', 'product')->get();

        $upcoming = LoanRepaymentSchedule::whereIn('status', ['pending', 'overdue'])
            ->whereHas('loan', fn ($q) => $q->where('organization_id', $org))
            ->with('loan.member')->orderBy('due_date')->limit(5)->get();

        $recent = Transaction::where('organization_id', $org)->with('member')->latest('created_at')->limit(6)->get();

        return view('admin.dashboard', compact(
            'summary', 'loanStatus', 'cashFlow', 'savingsVsLoans', 'pendingApprovals', 'upcoming', 'recent'
        ));
    }
}
