<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ReversalService;
use App\Support\Audit;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly ReversalService $reversals) {}

    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $payments = Payment::where('organization_id', $orgId)->with('member')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->method, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($request->q, fn ($q, $s) => $q->where('internal_reference', 'like', "%$s%")->orWhere('external_reference', 'like', "%$s%"))
            ->latest('paid_at')->paginate(15)->withQueryString();

        $summary = [
            'successful' => (int) Payment::where('organization_id', $orgId)->where('status', 'successful')->sum('amount'),
            'pending' => Payment::where('organization_id', $orgId)->where('status', 'pending')->count(),
        ];

        return view('admin.payments.index', compact('payments', 'summary'));
    }

    public function verify(Request $request, Payment $payment)
    {
        $payment->update(['status' => 'successful', 'verified_at' => now()]);
        Audit::log($request, 'VERIFY_PAYMENT', 'Payment', $payment->id);

        return back()->with('toast', t('payments.verify').' ✓');
    }

    public function reverse(Request $request, Payment $payment)
    {
        $this->reversals->reversePayment($request, $payment, $request->input('reason'));

        return back()->with('toast', t('common.reverse').' ✓');
    }
}
