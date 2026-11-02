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
        $this->loans->markOverdue($orgId);
        $tab = $request->get('tab', 'all');
        $loans = Loan::where('organization_id', $orgId)->with('member', 'product')
            ->withMin(['schedule as next_due_date' => fn ($q) => $q->where('status', '!=', 'paid')], 'due_date')
            ->when(isset(self::TABS[$tab]), fn ($q) => $q->whereIn('status', self::TABS[$tab]))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('loan_number', 'like', "%$s%")
                ->orWhere('purpose', 'like', "%$s%")
                ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%"))))
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
        $this->loans->markOverdue($loan->organization_id, $loan->id);
        $loan->refresh()->load('member', 'product', 'schedule', 'repayments', 'guarantors.guarantorMember');
        $tab = $request->get('tab', 'overview');

        return view('admin.loans.show', compact('loan', 'tab'));
    }

    public function approve(Request $request, Loan $loan)
    {
        if (! in_array($loan->status, self::TABS['applications'], true)) {
            return back()->with('toast', t('loans.notAwaitingDecision'));
        }
        $loan->update(['status' => 'approved', 'approval_date' => now()->toDateString()]);
        Audit::log($request, 'APPROVE_LOAN', 'Loan', $loan->id, ['status' => $loan->getOriginal('status')], ['status' => 'approved']);

        return back()->with('toast', t('common.approve').' ✓');
    }

    public function reject(Request $request, Loan $loan)
    {
        if (! in_array($loan->status, [...self::TABS['applications'], 'approved'], true)) {
            return back()->with('toast', t('loans.notAwaitingDecision'));
        }
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
        if (! in_array($loan->status, ['active', 'disbursed', 'overdue', 'defaulted'], true)) {
            return back()->with('toast', t('loans.notRepayable'));
        }
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$loan->outstanding_balance],
            'method' => ['nullable', 'in:mobile_money,bank,card,cash,manual'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
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
