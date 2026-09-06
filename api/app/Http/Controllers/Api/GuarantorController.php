<?php

namespace App\Http\Controllers\Api;

use App\Models\Guarantor;
use App\Support\Audit;
use Illuminate\Http\Request;

class GuarantorController extends ApiController
{
    public function index(Request $request)
    {
        $q = Guarantor::query()
            ->where('organization_id', $this->orgId($request))
            ->with(['loan:id,loan_number', 'borrower:id,full_name,member_number,avatar_color', 'guarantorMember:id,full_name,member_number,avatar_color'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest();

        return $this->paginate($q, $request, fn (Guarantor $g) => [
            'id' => $g->id,
            'loanId' => $g->loan_id,
            'loanNumber' => $g->loan?->loan_number,
            'borrower' => $this->m($g->borrower),
            'guarantor' => $this->m($g->guarantorMember),
            'guaranteedAmount' => $g->guaranteed_amount,
            'status' => $g->status,
            'approvedAt' => $g->approved_at?->toDateString(),
        ]);
    }

    public function verify(Request $request, string $id)
    {
        $g = Guarantor::where('organization_id', $this->orgId($request))->findOrFail($id);
        $g->update(['status' => 'approved', 'approved_at' => now()]);
        Audit::log($request, 'VERIFY_GUARANTOR', 'Guarantor', $g->id, null, ['status' => 'approved']);

        return $this->item($g->fresh());
    }

    private function m($member): ?array
    {
        return $member ? ['id' => $member->id, 'fullName' => $member->full_name, 'memberNumber' => $member->member_number, 'avatarColor' => $member->avatar_color] : null;
    }
}
