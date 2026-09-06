<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use App\Services\ReversalService;
use App\Services\SavingsService;
use Illuminate\Http\Request;

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
            ->when($request->q, fn ($q, $s) => $q->where('account_number', 'like', "%$s%")
                ->orWhereHas('member', fn ($w) => $w->where('full_name', 'like', "%$s%")))
            ->orderByDesc('balance')->paginate(15)->withQueryString();

        $dep = (int) SavingsTransaction::where('organization_id', $orgId)->where('type', 'deposit')->sum('amount');
        $wd = (int) SavingsTransaction::where('organization_id', $orgId)->where('type', 'withdrawal')->sum('amount');
        $summary = ['deposits' => $dep, 'withdrawals' => $wd, 'net' => $dep - $wd];

        return view('admin.savings.index', compact('accounts', 'summary'));
    }

    public function show(SavingsAccount $account)
    {
        $account->load('member');
        $txns = $account->transactions()->orderBy('created_at')->get();

        return view('admin.savings.show', compact('account', 'txns'));
    }

    public function deposit(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:savings_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string'],
        ]);
        $this->savings->deposit($request, $data);

        return back()->with('toast', t('savings.recordDeposit').' ✓');
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:savings_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string'],
        ]);
        try {
            $this->savings->withdraw($request, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('toast', t('savings.recordWithdrawal').' ✓');
    }

    public function reverse(Request $request, Transaction $txn)
    {
        $this->reversals->reverseTransaction($request, $txn, $request->input('reason'));

        return back()->with('toast', t('common.reverse').' ✓');
    }
}
