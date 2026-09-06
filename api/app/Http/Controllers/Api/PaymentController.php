<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Support\Audit;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(Request $request)
    {
        $q = Payment::where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('method'), fn ($q, $m) => $q->where('payment_method', $m))
            ->latest('paid_at');

        $this->applySearch($q, $request, ['internal_reference', 'external_reference', 'provider', 'purpose']);

        return $this->paginate($q, $request, fn (Payment $p) => [
            'id' => $p->id,
            'memberId' => $p->member_id,
            'member' => $p->member ? ['id' => $p->member->id, 'fullName' => $p->member->full_name, 'memberNumber' => $p->member->member_number, 'avatarColor' => $p->member->avatar_color] : null,
            'provider' => $p->provider,
            'method' => $p->payment_method,
            'amount' => $p->amount,
            'externalRef' => $p->external_reference,
            'internalRef' => $p->internal_reference,
            'purpose' => $p->purpose,
            'status' => $p->status,
            'paidAt' => $p->paid_at?->toIso8601String(),
            'verifiedAt' => $p->verified_at?->toIso8601String(),
        ]);
    }

    public function verify(Request $request, string $id)
    {
        $p = Payment::where('organization_id', $this->orgId($request))->findOrFail($id);
        $p->update(['status' => 'successful', 'verified_at' => now()]);
        Audit::log($request, 'VERIFY_PAYMENT', 'Payment', $p->id, ['status' => 'pending'], ['status' => 'successful']);

        return $this->item($p->fresh());
    }
}
