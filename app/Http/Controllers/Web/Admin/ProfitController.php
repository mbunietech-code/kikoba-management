<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitDistribution;
use App\Services\ProfitDistributionService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfitController extends Controller
{
    public function index()
    {
        $distributions = ProfitDistribution::where('organization_id', app('kikoba.org')->id)
            ->latest('period_end')->get();

        return view('admin.profit.index', compact('distributions'));
    }

    public function create(Request $request, ProfitDistributionService $profits)
    {
        $orgId = app('kikoba.org')->id;
        $start = Carbon::parse($request->get('period_start', now()->startOfYear()->toDateString()));
        $end = Carbon::parse($request->get('period_end', now()->toDateString()));
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        return view('admin.profit.create', [
            'start' => $start,
            'end' => $end,
            'booksProfit' => $profits->booksProfit($orgId, $start->copy()->startOfDay(), $end->copy()->endOfDay()),
        ]);
    }

    public function store(Request $request, ProfitDistributionService $profits)
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
            'total_profit' => ['required', 'integer', 'min:1'],
            'reserve_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'basis' => ['required', Rule::in(ProfitDistributionService::BASES)],
        ]);
        $distribution = $profits->calculate($request, app('kikoba.org')->id, $data);

        return redirect()->route('admin.profit.show', $distribution)->with('toast', t('profit.calculated'));
    }

    public function approve(Request $request, ProfitDistribution $distribution, ProfitDistributionService $profits)
    {
        try {
            $profits->approve($request, $distribution);
        } catch (ValidationException $e) {
            return back()->with('toast', collect($e->errors())->flatten()->first())->with('toast_tone', 'error');
        }

        return back()->with('toast', t('profit.approvedToast'));
    }

    public function distribute(Request $request, ProfitDistribution $distribution, ProfitDistributionService $profits)
    {
        $data = $request->validate(['method' => ['required', Rule::in(ProfitDistributionService::PAYOUT_METHODS)]]);
        try {
            $profits->payOut($request, $distribution, $data['method']);
        } catch (ValidationException $e) {
            return back()->with('toast', collect($e->errors())->flatten()->first())->with('toast_tone', 'error');
        }

        return back()->with('toast', t('profit.paidToast'));
    }

    public function show(ProfitDistribution $distribution)
    {
        $distribution->load(['allocations' => fn ($q) => $q->with('member')->orderByDesc('amount')]);

        return view('admin.profit.show', compact('distribution'));
    }

    public function destroy(Request $request, ProfitDistribution $distribution)
    {
        if (in_array($distribution->status, ['approved', 'distributed'])) {
            return back()->with('toast', t('profit.cannotDelete'))->with('toast_tone', 'error');
        }
        $distribution->allocations()->delete();
        $distribution->delete();
        Audit::log($request, 'DELETE_PROFIT_DISTRIBUTION', 'ProfitDistribution', $distribution->id);

        return redirect()->route('admin.profit.index')->with('toast', t('common.deleted'));
    }
}
