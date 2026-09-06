<?php

namespace App\Http\Controllers\Api;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\LoanCalculationService;
use App\Services\LoanService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LoanController extends ApiController
{
    private const TABS = [
        'applications' => ['draft', 'submitted', 'under_review'],
        'active' => ['approved', 'disbursed', 'active'],
        'overdue' => ['overdue', 'defaulted'],
        'completed' => ['completed', 'rejected', 'cancelled'],
    ];

    public function __construct(
        private readonly LoanCalculationService $calc,
        private readonly LoanService $loans,
    ) {}

    public function index(Request $request)
    {
        $q = Loan::query()
            ->where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color', 'product:id,name')
            ->when($request->query('member_id'), fn ($q, $id) => $q->where('member_id', $id))
            ->when($request->query('tab'), fn ($q, $tab) => isset(self::TABS[$tab]) ? $q->whereIn('status', self::TABS[$tab]) : $q)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('application_date');

        $this->applySearch($q, $request, ['loan_number', 'purpose']);

        return $this->paginate($q, $request, fn (Loan $l) => $this->row($l));
    }

    public function show(Request $request, string $id)
    {
        $loan = Loan::where('organization_id', $this->orgId($request))
            ->with(['member', 'product', 'guarantors.guarantorMember', 'schedule', 'repayments'])
            ->findOrFail($id);

        return $this->item($loan, fn ($l) => (new \App\Http\Resources\GenericResource($l))->resolve());
    }

    public function summary(Request $request)
    {
        $org = $this->orgId($request);
        $disbursed = (int) Loan::where('organization_id', $org)->whereNotNull('disbursement_date')->sum('principal_amount');
        $outstanding = (int) Loan::where('organization_id', $org)->sum('outstanding_balance');
        $overdue = (int) Loan::where('organization_id', $org)->whereIn('status', ['overdue', 'defaulted'])->sum('outstanding_balance');
        $byStatus = Loan::where('organization_id', $org)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return ApiResponse::ok([
            'disbursed' => $disbursed,
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'byStatus' => $byStatus,
        ]);
    }

    public function quote(Request $request)
    {
        $data = $request->validate([
            'loan_product_id' => ['required', 'exists:loan_products,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'integer', 'min:1', 'max:60'],
        ]);
        $product = LoanProduct::findOrFail($data['loan_product_id']);
        $q = $this->calc->quote($product, $data['amount'], $data['period'], Carbon::now());

        return ApiResponse::ok([
            'principal' => $data['amount'],
            'interest' => $q['interest'],
            'fees' => $q['processing_fee'],
            'insurance' => $q['insurance'],
            'total' => $q['total'],
            'installment' => $q['installment'],
            'schedule' => $q['schedule'],
        ]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'loan_product_id' => ['required', 'exists:loan_products,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'integer', 'min:1', 'max:60'],
            'purpose' => ['nullable', 'string'],
            'repayment_frequency' => ['nullable', 'in:weekly,biweekly,monthly,quarterly'],
            'guarantors' => ['array'],
            'guarantors.*' => ['exists:members,id'],
        ]);

        $application = $this->loans->apply($request, $data);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($application))->resolve(), 'Application submitted');
    }

    public function approve(Request $request, string $id)
    {
        $loan = $this->loans->decide($request, $id, 'approve', $request->input('note'));

        return $this->item($loan);
    }

    public function reject(Request $request, string $id)
    {
        $loan = $this->loans->decide($request, $id, 'reject', $request->input('reason'));

        return $this->item($loan);
    }

    public function disburse(Request $request, string $id)
    {
        $loan = $this->loans->disburse($request, $id);

        return $this->item($loan);
    }

    public function repay(Request $request, string $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'in:mobile_money,bank,card,cash,manual'],
            'date' => ['nullable', 'date'],
        ]);
        $repayment = $this->loans->repay($request, $id, $data);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($repayment))->resolve(), 'Repayment recorded');
    }

    public function eligibility(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'loan_product_id' => ['required', 'exists:loan_products,id'],
        ]);
        $member = Member::with('savingsAccount')->findOrFail($data['member_id']);
        $product = LoanProduct::findOrFail($data['loan_product_id']);

        return ApiResponse::ok($this->loans->checkEligibility($member, $product));
    }

    private function row(Loan $l): array
    {
        return [
            'id' => $l->id,
            'loanNumber' => $l->loan_number,
            'memberId' => $l->member_id,
            'member' => $l->member ? ['id' => $l->member->id, 'fullName' => $l->member->full_name, 'memberNumber' => $l->member->member_number, 'avatarColor' => $l->member->avatar_color] : null,
            'productName' => $l->product?->name,
            'principal' => $l->principal_amount,
            'interest' => $l->interest_amount,
            'fees' => $l->processing_fee,
            'insurance' => $l->insurance_amount,
            'penalty' => $l->penalty_amount,
            'total' => $l->total_amount,
            'amountPaid' => $l->amount_paid,
            'outstanding' => $l->outstanding_balance,
            'status' => $l->status,
            'purpose' => $l->purpose,
            'period' => $l->period,
            'frequency' => $l->repayment_frequency,
            'interestMethod' => $l->interest_method,
            'applicationDate' => $l->application_date?->toDateString(),
            'approvalDate' => $l->approval_date?->toDateString(),
            'disbursementDate' => $l->disbursement_date?->toDateString(),
            'maturityDate' => $l->maturity_date?->toDateString(),
        ];
    }
}
