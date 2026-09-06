<?php

namespace App\Http\Controllers\Api;

use App\Models\Loan;
use App\Models\Member;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MeController extends ApiController
{
    private function member(Request $request): Member
    {
        $member = $request->user()->member;
        abort_unless($member, 403, 'This account is not linked to a member.');

        return $member->load('savingsAccount', 'insuranceAccount');
    }

    public function position(Request $request)
    {
        $m = $this->member($request);
        $shareValue = (int) $m->shares()->sum('total_value');
        $shareQty = (int) $m->shares()->sum('quantity');
        $loans = $m->loans()->with('product:id,name')->get();
        $activeLoan = $loans->firstWhere(fn ($l) => in_array($l->status, ['active', 'overdue', 'disbursed']));
        $invest = $m->projectInvestments()->get();

        return ApiResponse::ok([
            'member' => [
                'id' => $m->id, 'fullName' => $m->full_name, 'memberNumber' => $m->member_number,
                'status' => $m->status, 'avatarColor' => $m->avatar_color, 'phone' => $m->phone, 'email' => $m->email,
                'address' => $m->address, 'nextOfKin' => $m->next_of_kin, 'nextOfKinPhone' => $m->next_of_kin_phone,
                'registrationDate' => $m->registration_date?->toDateString(),
                'accountNumber' => $m->savingsAccount?->account_number,
            ],
            'shareValue' => $shareValue,
            'shareQty' => $shareQty,
            'savingsBalance' => (int) ($m->savingsAccount?->balance ?? 0),
            'loanOutstanding' => (int) $loans->sum('outstanding_balance'),
            'activeLoanId' => $activeLoan?->id,
            'projectInvestment' => (int) $invest->sum('amount'),
            'projectReturn' => (int) $invest->sum(fn ($i) => $i->amount + $i->profit_share),
            'profit' => (int) ($invest->sum('profit_share') + round($shareValue / 1_000_000 * 42000)),
            'insurance' => $m->insuranceAccount ? [
                'status' => $m->insuranceAccount->status,
                'coverageAmount' => $m->insuranceAccount->coverage_amount,
                'monthlyContribution' => $m->insuranceAccount->monthly_contribution,
                'totalContributed' => (int) $m->insuranceAccount->contributions()->sum('amount'),
                'planName' => $m->insuranceAccount->plan_name,
                'startDate' => $m->insuranceAccount->start_date?->toDateString(),
                'endDate' => $m->insuranceAccount->end_date?->toDateString(),
            ] : null,
        ]);
    }

    public function shares(Request $request)
    {
        return $this->items($this->member($request)->shares()->latest('purchased_at')->get(), fn ($s) => [
            'id' => $s->id, 'quantity' => $s->quantity, 'pricePerShare' => $s->price_per_share,
            'totalValue' => $s->total_value, 'purchasedAt' => $s->purchased_at?->toDateString(),
            'transactionRef' => $s->transaction_reference, 'status' => $s->status,
        ]);
    }

    public function savings(Request $request)
    {
        $m = $this->member($request);
        $account = $m->savingsAccount;
        $txns = $account ? $account->transactions()->orderBy('created_at')->get() : collect();

        return ApiResponse::ok([
            'account' => $account ? ['id' => $account->id, 'accountNumber' => $account->account_number, 'balance' => $account->balance, 'status' => $account->status] : null,
            'transactions' => $txns->map(fn ($t) => [
                'id' => $t->id, 'type' => $t->type, 'amount' => $t->amount,
                'balanceBefore' => $t->balance_before, 'balanceAfter' => $t->balance_after,
                'reference' => $t->reference, 'date' => $t->created_at?->toDateString(),
            ]),
        ]);
    }

    public function loans(Request $request)
    {
        return $this->items(
            $this->member($request)->loans()->with('product:id,name')->latest('application_date')->get(),
            fn (Loan $l) => [
                'id' => $l->id, 'loanNumber' => $l->loan_number, 'productName' => $l->product?->name,
                'principal' => $l->principal_amount, 'interest' => $l->interest_amount,
                'fees' => $l->processing_fee, 'insurance' => $l->insurance_amount,
                'total' => $l->total_amount, 'amountPaid' => $l->amount_paid, 'outstanding' => $l->outstanding_balance,
                'status' => $l->status, 'period' => $l->period, 'purpose' => $l->purpose,
                'applicationDate' => $l->application_date?->toDateString(),
                'disbursementDate' => $l->disbursement_date?->toDateString(),
                'maturityDate' => $l->maturity_date?->toDateString(),
            ],
        );
    }

    public function loan(Request $request, string $id)
    {
        $loan = $this->member($request)->loans()->with(['product', 'schedule', 'repayments', 'guarantors.guarantorMember'])->findOrFail($id);

        return $this->item($loan);
    }

    public function repayments(Request $request)
    {
        $m = $this->member($request);
        $loanIds = $m->loans()->pluck('id');

        return ApiResponse::ok([
            'history' => \App\Models\LoanRepayment::whereIn('loan_id', $loanIds)->with('loan:id,loan_number')->latest('payment_date')->get()
                ->map(fn ($r) => ['id' => $r->id, 'loanNumber' => $r->loan?->loan_number, 'reference' => $r->reference,
                    'principalPaid' => $r->principal_paid, 'interestPaid' => $r->interest_paid, 'totalPaid' => $r->total_paid,
                    'method' => $r->method, 'date' => $r->payment_date?->toDateString()]),
            'upcoming' => \App\Models\LoanRepaymentSchedule::whereIn('loan_id', $loanIds)
                ->whereIn('status', ['pending', 'partial', 'overdue'])->with('loan:id,loan_number')->orderBy('due_date')->get()
                ->map(fn ($r) => ['id' => $r->id, 'loanId' => $r->loan_id, 'loanNumber' => $r->loan?->loan_number,
                    'installment' => $r->installment_number, 'dueDate' => $r->due_date?->toDateString(),
                    'totalDue' => $r->total_due, 'amountPaid' => $r->amount_paid, 'status' => $r->status]),
        ]);
    }

    public function projects(Request $request)
    {
        return $this->items(
            $this->member($request)->projectInvestments()->with('project')->get(),
            fn ($i) => [
                'id' => $i->id, 'amount' => $i->amount, 'profitShare' => $i->profit_share, 'status' => $i->status,
                'investedAt' => $i->invested_at?->toDateString(),
                'project' => $i->project ? [
                    'id' => $i->project->id, 'name' => $i->project->name, 'type' => $i->project->type, 'status' => $i->project->status,
                    'capitalRequired' => $i->project->capital_required, 'capitalRaised' => $i->project->capital_raised,
                    'startDate' => $i->project->start_date?->toDateString(), 'endDate' => $i->project->end_date?->toDateString(),
                ] : null,
            ],
        );
    }

    public function insurance(Request $request)
    {
        $m = $this->member($request);
        $acc = $m->insuranceAccount;
        if (! $acc) {
            return ApiResponse::ok(['account' => null, 'contributions' => [], 'claims' => []]);
        }

        return ApiResponse::ok([
            'account' => [
                'planName' => $acc->plan_name, 'monthlyContribution' => $acc->monthly_contribution,
                'coverageAmount' => $acc->coverage_amount, 'status' => $acc->status,
                'startDate' => $acc->start_date?->toDateString(), 'endDate' => $acc->end_date?->toDateString(),
                'totalContributed' => (int) $acc->contributions()->sum('amount'),
            ],
            'contributions' => $acc->contributions()->latest('paid_on')->get()
                ->map(fn ($c) => ['id' => $c->id, 'amount' => $c->amount, 'period' => $c->period, 'reference' => $c->reference, 'date' => $c->paid_on?->toDateString()]),
            'claims' => $m->insuranceClaims()->latest('submitted_at')->get()
                ->map(fn ($c) => ['id' => $c->id, 'claimNumber' => $c->claim_number, 'claimType' => $c->claim_type,
                    'amountRequested' => $c->amount_requested, 'amountApproved' => $c->amount_approved, 'status' => $c->status]),
        ]);
    }

    public function transactions(Request $request)
    {
        return $this->items(
            $this->member($request)->transactions()->latest('created_at')->get(),
            fn ($t) => ['id' => $t->id, 'reference' => $t->transaction_reference, 'type' => strtolower($t->type),
                'amount' => $t->amount, 'status' => $t->status, 'createdAt' => $t->created_at?->toIso8601String()],
        );
    }

    public function updateProfile(Request $request)
    {
        $m = $this->member($request);
        $data = $request->validate([
            'phone' => ['sometimes', 'string'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'next_of_kin' => ['nullable', 'string'],
            'next_of_kin_phone' => ['nullable', 'string'],
        ]);
        $m->update($data);

        return $this->item($m->fresh());
    }
}
