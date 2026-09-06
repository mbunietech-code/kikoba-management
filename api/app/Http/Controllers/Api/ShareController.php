<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\Share;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class ShareController extends ApiController
{
    public function index(Request $request)
    {
        $q = Share::query()
            ->where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color')
            ->when($request->query('member_id'), fn ($q, $id) => $q->where('member_id', $id))
            ->latest('purchased_at');

        return $this->paginate($q, $request, fn (Share $s) => [
            'id' => $s->id,
            'memberId' => $s->member_id,
            'member' => $s->member ? ['id' => $s->member->id, 'fullName' => $s->member->full_name, 'memberNumber' => $s->member->member_number, 'avatarColor' => $s->member->avatar_color] : null,
            'quantity' => $s->quantity,
            'pricePerShare' => $s->price_per_share,
            'totalValue' => $s->total_value,
            'purchasedAt' => $s->purchased_at?->toDateString(),
            'transactionRef' => $s->transaction_reference,
            'status' => $s->status,
        ]);
    }

    public function summary(Request $request)
    {
        $base = Share::where('organization_id', $this->orgId($request));

        return ApiResponse::ok([
            'shareCapital' => (int) (clone $base)->sum('total_value'),
            'sharesOutstanding' => (int) (clone $base)->sum('quantity'),
            'holders' => (clone $base)->distinct('member_id')->count('member_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price_per_share' => ['required', 'integer', 'min:1'],
            'purchased_at' => ['nullable', 'date'],
        ]);

        $member = Member::findOrFail($data['member_id']);
        $share = Share::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'quantity' => $data['quantity'],
            'price_per_share' => $data['price_per_share'],
            'total_value' => $data['quantity'] * $data['price_per_share'],
            'purchased_at' => $data['purchased_at'] ?? now()->toDateString(),
            'transaction_reference' => 'TXN-2026-'.str_pad((string) (Share::count() + 1), 6, '0', STR_PAD_LEFT),
            'status' => 'confirmed',
        ]);

        Audit::log($request, 'SHARE_PURCHASE', 'Share', $share->id, null, ['total' => $share->total_value]);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($share))->resolve(), 'Share purchase recorded');
    }
}
