<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use App\Services\ReversalService;
use App\Services\SavingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SavingsController extends Controller
{
    public function __construct(
        private readonly SavingsService $savings,
        private readonly ReversalService $reversals,
    ) {}

    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $accounts = SavingsAccount::where('organization_id', $orgId)->with('member')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('account_number', 'like', "%$s%")
                ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%"))))
            ->orderByDesc('balance')->paginate(15)->withQueryString();

        $summary = [
            'deposits' => $this->effectiveTotal($orgId, 'deposit'),
            'withdrawals' => $this->effectiveTotal($orgId, 'withdrawal'),
            'net' => (int) SavingsAccount::where('organization_id', $orgId)->sum('balance'),
        ];

        return view('admin.savings.index', [
            'accounts' => $accounts,
            'summary' => $summary,
            'accountOptions' => $this->accountOptions($orgId),
        ]);
    }

    public function show(SavingsAccount $account)
    {
        $account->load('member');
        $txns = $account->transactions()->with('transaction:id,status,type,transaction_reference')->latest('created_at')->get();
        $reversedIds = $txns->pluck('reversal_of')->filter()->all();

        return view('admin.savings.show', [
            'account' => $account,
            'txns' => $txns,
            'reversedIds' => $reversedIds,
            'deposits' => $txns->where('type', 'deposit')->whereNotIn('id', $reversedIds)->sum('amount'),
            'withdrawals' => $txns->where('type', 'withdrawal')->whereNotIn('id', $reversedIds)->sum('amount'),
        ]);
    }

    /** Deposits or withdrawals that have not been reversed. */
    private function effectiveTotal(string $orgId, string $type): int
    {
        return (int) SavingsTransaction::where('organization_id', $orgId)->where('type', $type)
            ->whereNotIn('id', SavingsTransaction::where('organization_id', $orgId)->whereNotNull('reversal_of')->select('reversal_of'))
            ->sum('amount');
    }

    /**
     * Every account in the group for the deposit / withdrawal pickers (not just the visible page).
     *
     * @return Collection<int, SavingsAccount>
     */
    private function accountOptions(string $orgId)
    {
        return SavingsAccount::where('organization_id', $orgId)->where('status', '!=', 'closed')
            ->with('member:id,full_name,member_number')
            ->get(['id', 'member_id', 'account_number', 'balance'])
            ->sortBy(fn ($a) => $a->member?->full_name)->values();
    }

    public function deposit(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:savings_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        $this->savings->deposit($request, $data);

        return back()->with('toast', t('savings.recordDeposit').' ✓');
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:savings_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        try {
            $this->savings->withdraw($request, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('toast', t('savings.recordWithdrawal').' ✓');
    }

    public function reverse(Request $request, Transaction $txn)
    {
        $request->validate(['reason' => ['required', 'string', 'max:255']]);
        try {
            $this->reversals->reverseTransaction($request, $txn, $request->input('reason'));
        } catch (ValidationException $e) {
            return back()->with('toast', collect($e->errors())->flatten()->first())->with('toast_tone', 'error');
        }

        return back()->with('toast', t('common.reverse').' ✓');
    }
}
