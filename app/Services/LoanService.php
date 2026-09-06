<?php

namespace App\Services;

use App\Models\Guarantor;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanService
{
    public function __construct(private readonly LoanCalculationService $calc) {}

    public function checkEligibility(Member $member, LoanProduct $product): array
    {
        $shares = (int) $member->shares()->sum('quantity');
        $savings = (int) ($member->savingsAccount?->balance ?? 0);
        $hasOverdue = $member->loans()->whereIn('status', ['overdue', 'defaulted'])->exists();

        $checks = [
            ['key' => 'active', 'label' => 'Member is active', 'passed' => $member->status === 'active'],
            ['key' => 'shares', 'label' => "Minimum shares: {$product->minimum_shares}", 'passed' => $shares >= $product->minimum_shares],
            ['key' => 'savings', 'label' => "Minimum savings: {$product->minimum_savings}", 'passed' => $savings >= $product->minimum_savings],
            ['key' => 'no_overdue', 'label' => 'No overdue loan', 'passed' => ! $hasOverdue],
        ];

        return [
            'eligible' => collect($checks)->every('passed'),
            'checks' => $checks,
            'shares' => $shares,
            'savings' => $savings,
        ];
    }

    public function apply(Request $request, array $data): LoanApplication
    {
        $member = Member::findOrFail($data['member_id']);
        $product = LoanProduct::findOrFail($data['loan_product_id']);

        $application = LoanApplication::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'requested_amount' => $data['amount'],
            'purpose' => $data['purpose'] ?? null,
            'requested_period' => $data['period'],
            'repayment_frequency' => $data['repayment_frequency'] ?? 'monthly',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $application;
    }

    public function decide(Request $request, string $loanOrAppId, string $decision, ?string $note): Loan
    {
        return DB::transaction(function () use ($request, $loanOrAppId, $decision, $note) {
            $loan = Loan::find($loanOrAppId);

            if (! $loan) {
                // treat as application id → create the loan on approval
                $app = LoanApplication::with('product', 'member')->findOrFail($loanOrAppId);
                if ($decision === 'reject') {
                    $app->update(['status' => 'rejected', 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id, 'rejection_reason' => $note]);
                    throw new RuntimeException('Application rejected'); // not reachable path for loan return
                }
                $loan = $this->createLoanFromApplication($app, $request);
            }

            if (! in_array($loan->status, ['submitted', 'under_review', 'draft'])) {
                if ($decision === 'reject') {
                    $loan->update(['status' => 'rejected']);
                    Audit::log($request, 'REJECT_LOAN', 'Loan', $loan->id, ['status' => $loan->getOriginal('status')], ['status' => 'rejected']);

                    return $loan->fresh();
                }
            }

            $before = $loan->status;
            $loan->update([
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'approval_date' => $decision === 'approve' ? now()->toDateString() : null,
            ]);
            Audit::log($request, $decision === 'approve' ? 'APPROVE_LOAN' : 'REJECT_LOAN', 'Loan', $loan->id, ['status' => $before], ['status' => $loan->status]);

            return $loan->fresh();
        });
    }

    private function createLoanFromApplication(LoanApplication $app, Request $request): Loan
    {
        $q = $this->calc->quote($app->product, $app->requested_amount, $app->requested_period);
        $next = Loan::count() + 1;

        return Loan::create([
            'organization_id' => $app->organization_id,
            'member_id' => $app->member_id,
            'loan_product_id' => $app->loan_product_id,
            'loan_application_id' => $app->id,
            'loan_number' => 'LN-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
            'principal_amount' => $app->requested_amount,
            'interest_amount' => $q['interest'],
            'processing_fee' => $q['processing_fee'],
            'insurance_amount' => $q['insurance'],
            'total_amount' => $q['total'],
            'amount_paid' => 0,
            'outstanding_balance' => $q['total'],
            'status' => 'under_review',
            'purpose' => $app->purpose,
            'period' => $app->requested_period,
            'repayment_frequency' => $app->repayment_frequency,
            'interest_method' => $app->product->interest_method,
            'application_date' => $app->submitted_at?->toDateString() ?? now()->toDateString(),
        ]);
    }

    public function disburse(Request $request, string $id): Loan
    {
        return DB::transaction(function () use ($request, $id) {
            /** @var Loan $loan */
            $loan = Loan::with('product')->lockForUpdate()->findOrFail($id);

            if ($loan->status !== 'approved') {
                throw new RuntimeException('Only approved loans can be disbursed.');
            }

            $start = Carbon::now();
            $q = $this->calc->quote($loan->product, $loan->principal_amount, $loan->period, $start);

            foreach ($q['schedule'] as $row) {
                LoanRepaymentSchedule::create([
                    'loan_id' => $loan->id,
                    'installment_number' => $row['installment'],
                    'due_date' => $row['due_date'],
                    'principal_due' => $row['principal_due'],
                    'interest_due' => $row['interest_due'],
                    'fee_due' => $row['fee_due'],
                    'total_due' => $row['total_due'],
                    'status' => 'pending',
                ]);
            }

            $reference = 'TXN-2026-'.str_pad((string) (Transaction::count() + 1), 6, '0', STR_PAD_LEFT);
            $txn = Transaction::create([
                'organization_id' => $loan->organization_id,
                'member_id' => $loan->member_id,
                'transaction_reference' => $reference,
                'type' => 'LOAN_DISBURSEMENT',
                'amount' => $loan->principal_amount,
                'status' => 'successful',
                'description' => "Disbursement of {$loan->loan_number}",
                'created_by' => $request->user()?->name,
            ]);

            $loan->update([
                'status' => 'active',
                'disbursement_date' => $start->toDateString(),
                'maturity_date' => $start->copy()->addMonths($loan->period)->toDateString(),
                'interest_amount' => $q['interest'],
                'total_amount' => $q['total'],
                'outstanding_balance' => $q['total'],
            ]);

            app(AccountingService::class)->post(
                $loan->organization_id, $reference, "Loan disbursement {$loan->loan_number}",
                [['1300', $loan->principal_amount, 0], ['1010', 0, $loan->principal_amount]], $txn->id,
            );

            Audit::log($request, 'DISBURSE_LOAN', 'Loan', $loan->id, ['status' => 'approved'], ['status' => 'active']);

            return $loan->fresh(['schedule']);
        });
    }

    public function repay(Request $request, string $id, array $data): LoanRepayment
    {
        return DB::transaction(function () use ($request, $id, $data) {
            /** @var Loan $loan */
            $loan = Loan::lockForUpdate()->findOrFail($id);
            $amount = (int) $data['amount'];

            // allocate: penalty -> interest -> fees -> principal, across due schedule rows
            $remaining = $amount;
            $alloc = ['penalty' => 0, 'interest' => 0, 'fee' => 0, 'principal' => 0];

            $rows = $loan->schedule()->whereIn('status', ['pending', 'partial', 'overdue'])->orderBy('installment_number')->get();
            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $due = $row->total_due - $row->amount_paid;
                $pay = min($remaining, $due);
                $remaining -= $pay;
                $row->increment('amount_paid', $pay);
                $row->update(['status' => $row->amount_paid >= $row->total_due ? 'paid' : 'partial', 'paid_at' => $row->amount_paid >= $row->total_due ? now()->toDateString() : null]);

                $ratioBase = max(1, $row->total_due);
                $alloc['interest'] += (int) round($pay * $row->interest_due / $ratioBase);
                $alloc['fee'] += (int) round($pay * $row->fee_due / $ratioBase);
                $alloc['principal'] += (int) round($pay * $row->principal_due / $ratioBase);
            }

            $reference = 'TXN-2026-'.str_pad((string) (Transaction::count() + 1), 6, '0', STR_PAD_LEFT);
            $txn = Transaction::create([
                'organization_id' => $loan->organization_id,
                'member_id' => $loan->member_id,
                'transaction_reference' => $reference,
                'type' => 'LOAN_REPAYMENT',
                'amount' => $amount,
                'status' => 'successful',
                'description' => "Repayment for {$loan->loan_number}",
                'created_by' => $request->user()?->name,
            ]);

            $repayment = LoanRepayment::create([
                'organization_id' => $loan->organization_id,
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'transaction_id' => $txn->id,
                'principal_paid' => $alloc['principal'],
                'interest_paid' => $alloc['interest'],
                'fee_paid' => $alloc['fee'],
                'penalty_paid' => $alloc['penalty'],
                'total_paid' => $amount,
                'payment_date' => $data['date'] ?? now()->toDateString(),
                'reference' => $reference,
                'method' => $data['method'] ?? 'cash',
            ]);

            $paid = $loan->amount_paid + $amount;
            $outstanding = max(0, $loan->total_amount - $paid);
            $loan->update([
                'amount_paid' => $paid,
                'outstanding_balance' => $outstanding,
                'status' => $outstanding === 0 ? 'completed' : $loan->status,
            ]);

            app(AccountingService::class)->post(
                $loan->organization_id, $reference, "Loan repayment {$loan->loan_number}",
                [
                    ['1010', $amount, 0],
                    ['1300', 0, $alloc['principal']],
                    ['4000', 0, $alloc['interest']],
                    ['4200', 0, $amount - $alloc['principal'] - $alloc['interest']],
                ], $txn->id,
            );

            Audit::log($request, 'REPAY', 'Loan', $loan->id, null, ['amount' => $amount, 'outstanding' => $outstanding]);

            return $repayment;
        });
    }
}
