<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanService;
use App\Support\Audit;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    private const TABS = [
        'applications' => ['draft', 'submitted', 'under_review'],
        'active' => ['approved', 'disbursed', 'active'],
        'overdue' => ['overdue', 'defaulted'],
        'completed' => ['completed', 'rejected', 'cancelled'],
    ];

    public function __construct(private readonly LoanService $loans) {}

    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $tab = $request->get('tab', 'all');
        $loans = Loan::where('organization_id', $orgId)->with('member', 'product')
            ->when(isset(self::TABS[$tab]), fn ($q) => $q->whereIn('status', self::TABS[$tab]))
            ->when($request->q, fn ($q, $s) => $q->where('loan_number', 'like', "%$s%")->orWhere('purpose', 'like', "%$s%"))
            ->latest('application_date')->paginate(15)->withQueryString();

        $summary = [
            'disbursed' => (int) Loan::where('organization_id', $orgId)->whereNotNull('disbursement_date')->sum('principal_amount'),
            'outstanding' => (int) Loan::where('organization_id', $orgId)->sum('outstanding_balance'),
            'overdue' => (int) Loan::where('organization_id', $orgId)->whereIn('status', ['overdue', 'defaulted'])->sum('outstanding_balance'),
            'byStatus' => Loan::where('organization_id', $orgId)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ];

        return view('admin.loans.index', compact('loans', 'summary', 'tab'));
    }

    public function show(Request $request, Loan $loan)
    {
        $loan->load('member', 'product', 'schedule', 'repayments', 'guarantors.guarantorMember');
        $tab = $request->get('tab', 'overview');

        return view('admin.loans.show', compact('loan', 'tab'));
    }

    public function approve(Request $request, Loan $loan)
    {
        $loan->update(['status' => 'approved', 'approval_date' => now()->toDateString()]);
        Audit::log($request, 'APPROVE_LOAN', 'Loan', $loan->id, ['status' => $loan->getOriginal('status')], ['status' => 'approved']);

        return back()->with('toast', t('common.approve').' ✓');
    }

    public function reject(Request $request, Loan $loan)
    {
        $loan->update(['status' => 'rejected']);
        Audit::log($request, 'REJECT_LOAN', 'Loan', $loan->id);

        return back()->with('toast', t('common.reject').' ✓');
    }

    public function disburse(Request $request, Loan $loan)
    {
        try {
            $this->loans->disburse($request, $loan->id);
        } catch (\Throwable $e) {
            return back()->with('toast', $e->getMessage());
        }

        return back()->with('toast', t('loans.disburse').' ✓');
    }

    public function repay(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'in:mobile_money,bank,card,cash,manual'],
        ]);
        $this->loans->repay($request, $loan->id, $data);

        return back()->with('toast', t('loans.recordRepayment').' ✓');
    }

    public function cancel(Request $request, Loan $loan)
    {
        if (in_array($loan->status, ['active', 'overdue', 'completed', 'defaulted'])) {
            return back()->with('toast', 'Disbursed loan cannot be cancelled');
        }
        $loan->update(['status' => 'cancelled']);
        Audit::log($request, 'CANCEL_LOAN', 'Loan', $loan->id);

        return redirect()->route('admin.loans.index')->with('toast', t('common.deleted'));
    }
}
