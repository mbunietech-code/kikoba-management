<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberController extends ApiController
{
    public function index(Request $request)
    {
        $q = Member::query()
            ->where('organization_id', $this->orgId($request))
            ->withSum('shares as shares_value', 'total_value')
            ->with('savingsAccount:id,member_id,balance,account_number')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('gender'), fn ($q, $g) => $q->where('gender', $g))
            ->orderBy('member_number');

        $this->applySearch($q, $request, ['full_name', 'member_number', 'phone', 'email']);

        return $this->paginate($q, $request, fn (Member $m) => [
            'id' => $m->id,
            'memberNumber' => $m->member_number,
            'fullName' => $m->full_name,
            'phone' => $m->phone,
            'email' => $m->email,
            'gender' => $m->gender,
            'status' => $m->status,
            'avatarColor' => $m->avatar_color,
            'communityGroup' => $m->community_group,
            'registrationDate' => $m->registration_date?->toDateString(),
            'sharesValue' => (int) ($m->shares_value ?? 0),
            'savingsBalance' => (int) ($m->savingsAccount?->balance ?? 0),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $m = Member::where('organization_id', $this->orgId($request))
            ->with([
                'shares', 'savingsAccount', 'savingsTransactions' => fn ($q) => $q->latest('created_at')->limit(50),
                'loans.product', 'insuranceAccount', 'guaranteeing.loan',
                'transactions' => fn ($q) => $q->latest()->limit(50),
            ])
            ->findOrFail($id);

        return $this->item($m);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'community_group' => ['nullable', 'string', 'max:120'],
            'next_of_kin' => ['nullable', 'string'],
            'next_of_kin_phone' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,active,suspended,inactive'],
        ]);

        $orgId = $this->orgId($request);
        $next = Member::where('organization_id', $orgId)->count() + 1;

        $member = Member::create([
            ...$data,
            'organization_id' => $orgId,
            'member_number' => 'MBR-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
            'registration_date' => now()->toDateString(),
            'status' => $data['status'] ?? 'pending',
            'avatar_color' => '#'.substr(md5($data['full_name']), 0, 6),
        ]);

        Audit::log($request, 'CREATE_MEMBER', 'Member', $member->id, null, ['status' => $member->status]);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($member))->resolve(), 'Member created');
    }

    public function update(Request $request, string $id)
    {
        $member = Member::where('organization_id', $this->orgId($request))->findOrFail($id);
        $before = $member->only(['full_name', 'phone', 'email', 'status', 'address']);

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:150'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'community_group' => ['nullable', 'string', 'max:120'],
            'next_of_kin' => ['nullable', 'string'],
            'next_of_kin_phone' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:pending,active,suspended,inactive,deceased'],
        ]);

        $member->update($data);
        Audit::log($request, 'UPDATE_MEMBER', 'Member', $member->id, $before, $member->only(array_keys($before)));

        return $this->item($member->fresh());
    }

    public function destroy(Request $request, string $id)
    {
        $member = Member::where('organization_id', $this->orgId($request))->findOrFail($id);

        $hasRecords = $member->loans()->exists()
            || $member->shares()->exists()
            || (int) $member->savingsAccount?->balance > 0
            || $member->projectInvestments()->exists()
            || $member->insuranceAccount()->exists();

        if ($hasRecords) {
            $member->update(['status' => 'inactive']);
            Audit::log($request, 'DEACTIVATE_MEMBER', 'Member', $id, null, ['status' => 'inactive']);

            return ApiResponse::message('Member has financial records — deactivated instead of deleted.');
        }

        $member->delete();
        Audit::log($request, 'DELETE_MEMBER', 'Member', $id);

        return ApiResponse::message('Member archived');
    }
}
