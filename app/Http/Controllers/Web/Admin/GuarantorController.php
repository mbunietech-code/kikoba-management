<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guarantor;
use App\Support\Audit;
use Illuminate\Http\Request;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $guarantors = Guarantor::where('organization_id', $orgId)
            ->with('loan', 'borrower', 'guarantorMember')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('loan_number', 'like', "%$s%")
                ->orWhereHas('borrower', fn ($m) => $m->where('full_name', 'like', "%$s%"))
                ->orWhereHas('guarantorMember', fn ($m) => $m->where('full_name', 'like', "%$s%"))))
            ->latest()->paginate(20)->withQueryString();

        // Totals cover the whole group, not just the page on screen.
        $summary = [
            'total' => Guarantor::where('organization_id', $orgId)->count(),
            'pending' => Guarantor::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'approvedAmount' => (int) Guarantor::where('organization_id', $orgId)->where('status', 'approved')->sum('guaranteed_amount'),
        ];

        return view('admin.guarantors.index', compact('guarantors', 'summary'));
    }

    public function verify(Request $request, Guarantor $guarantor)
    {
        $guarantor->update(['status' => 'approved', 'approved_at' => now()]);
        Audit::log($request, 'VERIFY_GUARANTOR', 'Guarantor', $guarantor->id);

        return back()->with('toast', t('guarantors.verify').' ✓');
    }

    public function destroy(Request $request, Guarantor $guarantor)
    {
        $guarantor->update(['status' => 'released']);
        Audit::log($request, 'RELEASE_GUARANTOR', 'Guarantor', $guarantor->id);

        return back()->with('toast', t('guarantors.status.released'));
    }
}
