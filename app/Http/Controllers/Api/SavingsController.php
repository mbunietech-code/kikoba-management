<?php

namespace App\Http\Controllers\Api;

use App\Models\SavingsAccount;
use App\Services\SavingsService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SavingsController extends ApiController
{
    public function __construct(private readonly SavingsService $savings) {}

    public function index(Request $request)
    {
        $q = SavingsAccount::query()
            ->where('organization_id', $this->orgId($request))
            ->with('member:id,full_name,member_number,avatar_color')
            ->orderByDesc('balance');

        $this->applySearch($q, $request, ['account_number']);

        return $this->paginate($q, $request, fn (SavingsAccount $a) => [
            'id' => $a->id,
            'memberId' => $a->member_id,
            'member' => $a->member ? ['id' => $a->member->id, 'fullName' => $a->member->full_name, 'memberNumber' => $a->member->member_number, 'avatarColor' => $a->member->avatar_color] : null,
            'accountNumber' => $a->account_number,
            'balance' => $a->balance,
            'status' => $a->status,
            'openedAt' => $a->opened_at?->toDateString(),
        ]);
    }

    public function summary(Request $request)
    {
        $org = $this->orgId($request);
        $deposits = (int) \App\Models\SavingsTransaction::where('organization_id', $org)->where('type', 'deposit')->sum('amount');
        $withdrawals = (int) \App\Models\SavingsTransaction::where('organization_id', $org)->where('type', 'withdrawal')->sum('amount');

        return ApiResponse::ok([
            'totalDeposits' => $deposits,
            'totalWithdrawals' => $withdrawals,
            'netSavings' => $deposits - $withdrawals,
            'totalBalance' => (int) SavingsAccount::where('organization_id', $org)->sum('balance'),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $account = SavingsAccount::where('organization_id', $this->orgId($request))
            ->with(['member', 'transactions' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($id);

        return $this->item($account);
    }

    public function deposit(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:savings_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);
        $txn = $this->savings->deposit($request, $data);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($txn))->resolve(), 'Deposit recorded');
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:savings_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);
        $txn = $this->savings->withdraw($request, $data);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($txn))->resolve(), 'Withdrawal recorded');
    }
}
