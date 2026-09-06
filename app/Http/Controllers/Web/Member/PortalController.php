<?php

namespace App\Http\Controllers\Web\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Services\LoanCalculationService;
use App\Support\Audit;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    private function member(Request $request): Member
    {
        $m = $request->user()->member;
        abort_unless($m, 403, 'This account is not linked to a member.');

        return $m->load('savingsAccount', 'insuranceAccount');
    }

    private function position(Member $m): array
    {
        $shareValue = (int) $m->shares()->sum('total_value');
        $shareQty = (int) $m->shares()->sum('quantity');
        $loans = $m->loans()->with('product')->get();
        $active = $loans->first(fn ($l) => in_array($l->status, ['active', 'overdue', 'disbursed']));
        $inv = $m->projectInvestments()->get();

        return [
            'shareValue' => $shareValue,
            'shareQty' => $shareQty,
            'savingsBalance' => (int) ($m->savingsAccount?->balance ?? 0),
            'loanOutstanding' => (int) $loans->sum('outstanding_balance'),
            'activeLoan' => $active,
            'loans' => $loans,
            'projectInvestment' => (int) $inv->sum('amount'),
            'profit' => (int) ($inv->sum('profit_share') + round($shareValue / 1_000_000 * 42000)),
            'insurance' => $m->insuranceAccount,
        ];
    }

    public function home(Request $request)
    {
        $member = $this->member($request);
        $pos = $this->position($member);
        $txns = $member->transactions()->latest('created_at')->limit(6)->get();
        $nextRepay = $pos['activeLoan']
            ? LoanRepaymentSchedule::where('loan_id', $pos['activeLoan']->id)
                ->whereIn('status', ['pending', 'overdue'])->orderBy('due_date')->first()
            : null;

        return view('member.home', compact('member', 'pos', 'txns', 'nextRepay'));
    }

    public function shares(Request $request)
    {
        $member = $this->member($request);
        $shares = $member->shares()->latest('purchased_at')->get();

        return view('member.shares', compact('member', 'shares'));
    }

    public function savings(Request $request)
    {
        $member = $this->member($request);
        $account = $member->savingsAccount;
        $txns = $account ? $account->transactions()->orderBy('created_at')->get() : collect();

        return view('member.savings', compact('member', 'account', 'txns'));
    }

    public function loans(Request $request)
    {
        $member = $this->member($request);
        $loans = $member->loans()->with('product')->latest('application_date')->get();

        return view('member.loans', compact('member', 'loans'));
    }

    public function loan(Request $request, Loan $loan)
    {
        abort_unless($loan->member_id === $this->member($request)->id, 404);
        $loan->load('product', 'schedule', 'repayments', 'guarantors.guarantorMember');
        $tab = $request->get('tab', 'schedule');

        return view('member.loan-show', compact('loan', 'tab'));
    }

    public function applyForm(Request $request)
    {
        $member = $this->member($request);
        $products = LoanProduct::where('organization_id', $member->organization_id)->where('status', 'active')->get();
        $others = Member::where('organization_id', $member->organization_id)->where('status', 'active')
            ->whereNot('id', $member->id)->orderBy('full_name')->get(['id', 'full_name', 'member_number']);

        return view('member.loan-apply', compact('member', 'products', 'others'));
    }

    public function apply(Request $request, LoanCalculationService $calc)
    {
        $member = $this->member($request);
        $data = $request->validate([
            'loan_product_id' => ['required', 'exists:loan_products,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'integer', 'min:1', 'max:36'],
            'purpose' => ['nullable', 'string'],
        ]);
        \App\Models\LoanApplication::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'loan_product_id' => $data['loan_product_id'],
            'requested_amount' => $data['amount'],
            'requested_period' => $data['period'],
            'purpose' => $data['purpose'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        Audit::log($request, 'LOAN_APPLICATION', 'LoanApplication', $member->id);

        return redirect()->route('member.loans')->with('toast', t('common.submit').' ✓');
    }

    public function repayments(Request $request)
    {
        $member = $this->member($request);
        $loanIds = $member->loans()->pluck('id');
        $history = LoanRepayment::whereIn('loan_id', $loanIds)->with('loan')->latest('payment_date')->get();
        $upcoming = LoanRepaymentSchedule::whereIn('loan_id', $loanIds)
            ->whereIn('status', ['pending', 'partial', 'overdue'])->with('loan')->orderBy('due_date')->get();

        return view('member.repayments', compact('member', 'history', 'upcoming'));
    }

    public function projects(Request $request)
    {
        $member = $this->member($request);
        $investments = $member->projectInvestments()->with('project')->get();

        return view('member.projects', compact('member', 'investments'));
    }

    public function project(Request $request, \App\Models\Project $project)
    {
        $member = $this->member($request);
        $mine = $project->investments()->where('member_id', $member->id)->first();
        $project->loadCount('investments as participant_count');

        return view('member.project-show', compact('project', 'mine'));
    }

    public function insurance(Request $request)
    {
        $member = $this->member($request);
        $account = $member->insuranceAccount;
        $contributions = $account ? $account->contributions()->latest('paid_on')->get() : collect();
        $claims = $member->insuranceClaims()->latest('submitted_at')->get();

        return view('member.insurance', compact('member', 'account', 'contributions', 'claims'));
    }

    public function transactions(Request $request)
    {
        $member = $this->member($request);
        $transactions = $member->transactions()->latest('created_at')->paginate(20);
        $types = $member->transactions()->distinct()->pluck('type');

        return view('member.transactions', compact('member', 'transactions', 'types'));
    }

    public function statements(Request $request)
    {
        $member = $this->member($request);
        $pos = $this->position($member);

        return view('member.statements', compact('member', 'pos'));
    }

    public function notifications(Request $request)
    {
        $notifications = \App\Models\Notification::where('organization_id', $this->member($request)->organization_id)
            ->latest('created_at')->paginate(30);

        return view('member.notifications', compact('notifications'));
    }

    public function profile(Request $request)
    {
        $member = $this->member($request);

        return view('member.profile', compact('member'));
    }

    public function updateProfile(Request $request)
    {
        $member = $this->member($request);
        $member->update($request->validate([
            'phone' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'next_of_kin' => ['nullable', 'string'],
            'next_of_kin_phone' => ['nullable', 'string'],
        ]));

        return back()->with('toast', t('common.saveChanges').' ✓');
    }
}
