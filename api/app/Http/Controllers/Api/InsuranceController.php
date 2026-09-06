<?php

namespace App\Http\Controllers\Api;

use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\InsuranceContribution;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class InsuranceController extends ApiController
{
    public function accounts(Request $request)
    {
        $q = InsuranceAccount::where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color')
            ->withSum('contributions as total_contributed', 'amount');

        return $this->paginate($q, $request, fn (InsuranceAccount $a) => [
            'id' => $a->id,
            'memberId' => $a->member_id,
            'member' => $this->m($a->member),
            'planName' => $a->plan_name,
            'monthlyContribution' => $a->monthly_contribution,
            'coverageAmount' => $a->coverage_amount,
            'startDate' => $a->start_date?->toDateString(),
            'endDate' => $a->end_date?->toDateString(),
            'status' => $a->status,
            'totalContributed' => (int) ($a->total_contributed ?? 0),
        ]);
    }

    public function claims(Request $request)
    {
        $q = InsuranceClaim::where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color')
            ->latest('submitted_at');

        return $this->paginate($q, $request, fn (InsuranceClaim $c) => [
            'id' => $c->id,
            'claimNumber' => $c->claim_number,
            'memberId' => $c->member_id,
            'member' => $this->m($c->member),
            'claimType' => $c->claim_type,
            'description' => $c->description,
            'amountRequested' => $c->amount_requested,
            'amountApproved' => $c->amount_approved,
            'status' => $c->status,
            'submittedAt' => $c->submitted_at?->toDateString(),
            'paidAt' => $c->paid_at?->toDateString(),
        ]);
    }

    public function summary(Request $request)
    {
        $org = $this->orgId($request);

        return ApiResponse::ok([
            'totalContributions' => (int) InsuranceContribution::where('organization_id', $org)->sum('amount'),
            'totalClaimsPaid' => (int) InsuranceClaim::where('organization_id', $org)->where('status', 'paid')->sum('amount_approved'),
            'membersCovered' => InsuranceAccount::where('organization_id', $org)->where('status', 'active')->count(),
            'pendingClaims' => InsuranceClaim::where('organization_id', $org)->whereIn('status', ['submitted', 'under_review'])->count(),
        ]);
    }

    public function contribute(Request $request)
    {
        $data = $request->validate([
            'insurance_account_id' => ['required', 'exists:insurance_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'string'],
        ]);
        $acc = InsuranceAccount::findOrFail($data['insurance_account_id']);
        $c = InsuranceContribution::create([
            'organization_id' => $acc->organization_id,
            'insurance_account_id' => $acc->id,
            'member_id' => $acc->member_id,
            'amount' => $data['amount'],
            'period' => $data['period'],
            'paid_on' => now()->toDateString(),
            'reference' => 'TXN-2026-'.str_pad((string) (\App\Models\Transaction::count() + 1), 6, '0', STR_PAD_LEFT),
        ]);
        Audit::log($request, 'INSURANCE_PAYMENT', 'InsuranceContribution', $c->id);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($c))->resolve(), 'Contribution recorded');
    }

    public function fileClaim(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'claim_type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'amount_requested' => ['required', 'integer', 'min:1'],
        ]);
        $acc = InsuranceAccount::where('member_id', $data['member_id'])->firstOrFail();
        $claim = InsuranceClaim::create([
            'organization_id' => $acc->organization_id,
            'insurance_account_id' => $acc->id,
            'member_id' => $data['member_id'],
            'claim_number' => 'CLM-'.str_pad((string) (InsuranceClaim::count() + 1), 5, '0', STR_PAD_LEFT),
            'claim_type' => $data['claim_type'],
            'description' => $data['description'] ?? null,
            'amount_requested' => $data['amount_requested'],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        Audit::log($request, 'FILE_CLAIM', 'InsuranceClaim', $claim->id);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($claim))->resolve(), 'Claim submitted');
    }

    public function decideClaim(Request $request, string $id)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,pay'],
            'amount_approved' => ['nullable', 'integer', 'min:0'],
        ]);
        $claim = InsuranceClaim::where('organization_id', $this->orgId($request))->findOrFail($id);
        $map = ['approve' => 'approved', 'reject' => 'rejected', 'pay' => 'paid'];
        $claim->update([
            'status' => $map[$data['decision']],
            'amount_approved' => $data['amount_approved'] ?? $claim->amount_approved,
            'approved_at' => in_array($data['decision'], ['approve', 'pay']) ? now() : $claim->approved_at,
            'paid_at' => $data['decision'] === 'pay' ? now() : $claim->paid_at,
        ]);
        Audit::log($request, strtoupper($data['decision']).'_CLAIM', 'InsuranceClaim', $claim->id);

        return $this->item($claim->fresh());
    }

    private function m($member): ?array
    {
        return $member ? ['id' => $member->id, 'fullName' => $member->full_name, 'memberNumber' => $member->member_number, 'avatarColor' => $member->avatar_color] : null;
    }
}
