<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityContribution;
use App\Models\Member;
use App\Services\CommunityContributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * "Jamii" — the Jamii and Dharura funds members contribute to every month,
 * and members organised by their solidarity group (community_group).
 */
class CommunityController extends Controller
{
    public function __construct(private readonly CommunityContributionService $contributions) {}

    public function index(Request $request)
    {
        $tab = $request->get('tab') === 'groups' ? 'groups' : 'contributions';

        return $tab === 'groups' ? $this->groups() : $this->contributionsTab($request);
    }

    private function contributionsTab(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $period = preg_match('/^\d{4}-\d{2}$/', (string) $request->get('month')) ? $request->get('month') : now()->format('Y-m');

        $paid = CommunityContribution::where('organization_id', $orgId)->counted()->where('period', $period)
            ->selectRaw('member_id, fund, sum(amount) as total')->groupBy('member_id', 'fund')->get()
            ->groupBy('member_id')->map(fn ($rows) => $rows->pluck('total', 'fund')->map(fn ($v) => (int) $v));

        $members = Member::where('organization_id', $orgId)->where('status', 'active')
            ->orderBy('full_name')->get(['id', 'full_name', 'member_number', 'avatar_color', 'community_group']);

        $recent = CommunityContribution::where('organization_id', $orgId)->with('member:id,full_name,member_number')
            ->latest('created_at')->paginate(15)->withQueryString();

        $monthTotals = ['jamii' => $paid->sum(fn ($p) => $p['jamii'] ?? 0), 'dharura' => $paid->sum(fn ($p) => $p['dharura'] ?? 0)];

        return view('admin.community.contributions', [
            'period' => $period,
            'periodLabel' => Carbon::createFromFormat('!Y-m', $period)->translatedFormat('F Y'),
            'rates' => $this->contributions->rates($orgId),
            'funds' => $this->contributions->fundTotals($orgId),
            'monthTotals' => $monthTotals,
            'members' => $members,
            'paid' => $paid,
            'recent' => $recent,
        ]);
    }

    public function store(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $data = $request->validate([
            'member_id' => ['required', Rule::exists('members', 'id')->where('organization_id', $orgId)->whereNull('deleted_at')],
            'period' => ['required', 'date_format:Y-m'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'jamii_amount' => ['required', 'integer', 'min:0'],
            'dharura_amount' => ['required', 'integer', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->contributions->record(
                $request,
                Member::findOrFail($data['member_id']),
                $data['period'],
                $data['paid_on'],
                ['jamii' => (int) $data['jamii_amount'], 'dharura' => (int) $data['dharura_amount']],
                $data['reference'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withInput()->with('toast', collect($e->errors())->flatten()->first())->with('toast_tone', 'error');
        }

        return redirect()->route('admin.community.index', ['month' => $data['period']])->with('toast', t('community.recorded'));
    }

    public function reverse(Request $request, CommunityContribution $contribution)
    {
        abort_unless($contribution->organization_id === app('kikoba.org')->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->contributions->reverse($request, $contribution, $data['reason']);
        } catch (ValidationException $e) {
            return back()->with('toast', collect($e->errors())->flatten()->first())->with('toast_tone', 'error');
        }

        return back()->with('toast', t('common.reverse').' ✓');
    }

    public function rates(Request $request)
    {
        $data = $request->validate([
            'jamii' => ['required', 'integer', 'min:1'],
            'dharura' => ['required', 'integer', 'min:1'],
        ]);
        $this->contributions->updateRates($request, app('kikoba.org')->id, ['jamii' => (int) $data['jamii'], 'dharura' => (int) $data['dharura']]);

        return back()->with('toast', t('community.ratesSaved'));
    }

    private function groups()
    {
        $orgId = app('kikoba.org')->id;

        $rows = Member::where('organization_id', $orgId)
            ->selectRaw("COALESCE(NULLIF(community_group, ''), '__none__') as grp")
            ->selectRaw('count(*) as members')
            ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as active")
            ->groupBy('grp')
            ->orderByRaw("grp = '__none__'")
            ->orderBy('grp')
            ->get();

        $shareByGroup = DB::table('shares')
            ->join('members', 'members.id', '=', 'shares.member_id')
            ->where('shares.organization_id', $orgId)
            ->whereNull('members.deleted_at')
            ->selectRaw("COALESCE(NULLIF(members.community_group, ''), '__none__') as grp, sum(shares.total_value) as v")
            ->groupBy('grp')->pluck('v', 'grp');

        $savingsByGroup = DB::table('savings_accounts')
            ->join('members', 'members.id', '=', 'savings_accounts.member_id')
            ->where('savings_accounts.organization_id', $orgId)
            ->whereNull('members.deleted_at')
            ->selectRaw("COALESCE(NULLIF(members.community_group, ''), '__none__') as grp, sum(savings_accounts.balance) as v")
            ->groupBy('grp')->pluck('v', 'grp');

        $groups = $rows->map(fn ($r) => [
            'name' => $r->grp,
            'label' => $r->grp === '__none__' ? t('community.ungrouped') : $r->grp,
            'members' => (int) $r->members,
            'active' => (int) $r->active,
            'shares' => (int) ($shareByGroup[$r->grp] ?? 0),
            'savings' => (int) ($savingsByGroup[$r->grp] ?? 0),
        ]);

        return view('admin.community.index', ['groups' => $groups]);
    }
}
