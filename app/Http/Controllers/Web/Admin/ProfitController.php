<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitDistribution;
use App\Support\Audit;
use Illuminate\Http\Request;

class ProfitController extends Controller
{
    public function index()
    {
        $distributions = ProfitDistribution::where('organization_id', app('kikoba.org')->id)
            ->latest('period_end')->get();

        return view('admin.profit.index', compact('distributions'));
    }

    public function show(ProfitDistribution $distribution)
    {
        $distribution->load(['allocations' => fn ($q) => $q->with('member')->orderByDesc('amount')]);

        return view('admin.profit.show', compact('distribution'));
    }

    public function destroy(Request $request, ProfitDistribution $distribution)
    {
        if (in_array($distribution->status, ['approved', 'distributed'])) {
            return back()->with('toast', 'Distributed — cannot delete');
        }
        $distribution->allocations()->delete();
        $distribution->delete();
        Audit::log($request, 'DELETE_PROFIT_DISTRIBUTION', 'ProfitDistribution', $distribution->id);

        return redirect()->route('admin.profit.index')->with('toast', t('common.deleted'));
    }
}
