<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use App\Services\ReversalService;
use Illuminate\Http\Request;

class TransactionController extends ApiController
{
    public function __construct(private readonly ReversalService $reversals) {}

    public function index(Request $request)
    {
        $q = Transaction::where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color')
            ->when($request->query('member_id'), fn ($q, $id) => $q->where('member_id', $id))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest('created_at');

        $this->applySearch($q, $request, ['transaction_reference', 'description']);

        return $this->paginate($q, $request, fn (Transaction $t) => $this->row($t));
    }

    public function show(Request $request, string $id)
    {
        return $this->item($this->find($request, Transaction::class, $id), fn ($t) => $this->row($t));
    }

    public function reverse(Request $request, string $id)
    {
        $txn = $this->find($request, Transaction::class, $id);
        $reversal = $this->reversals->reverseTransaction($request, $txn, $request->input('reason'));

        return $this->item($reversal, fn ($t) => $this->row($t));
    }

    private function row(Transaction $t): array
    {
        return [
            'id' => $t->id,
            'reference' => $t->transaction_reference,
            'memberId' => $t->member_id,
            'member' => $t->member ? ['id' => $t->member->id, 'fullName' => $t->member->full_name, 'memberNumber' => $t->member->member_number, 'avatarColor' => $t->member->avatar_color] : null,
            'type' => strtolower($t->type),
            'amount' => $t->amount,
            'status' => $t->status,
            'description' => $t->description,
            'createdBy' => $t->created_by,
            'createdAt' => $t->created_at?->toIso8601String(),
        ];
    }
}
