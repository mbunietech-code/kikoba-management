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
        $guarantors = Guarantor::where('organization_id', app('kikoba.org')->id)
            ->with('loan', 'borrower', 'guarantorMember')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.guarantors.index', compact('guarantors'));
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
