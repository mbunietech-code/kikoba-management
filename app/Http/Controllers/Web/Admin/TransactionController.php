<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReversalService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private readonly ReversalService $reversals) {}

    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $transactions = Transaction::where('organization_id', $orgId)->with('member')
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->q, fn ($q, $s) => $q->where('transaction_reference', 'like', "%$s%"))
            ->latest('created_at')->paginate(20)->withQueryString();

        $types = Transaction::where('organization_id', $orgId)->distinct()->pluck('type');

        return view('admin.transactions.index', compact('transactions', 'types'));
    }

    public function reverse(Request $request, Transaction $txn)
    {
        $this->reversals->reverseTransaction($request, $txn, $request->input('reason'));

        return back()->with('toast', t('common.reverse').' ✓');
    }
}
