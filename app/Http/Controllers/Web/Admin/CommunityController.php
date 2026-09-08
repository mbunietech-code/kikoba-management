<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Jamii" — members organised by their solidarity group (community_group).
 */
class CommunityController extends Controller
{
    public function index(Request $request)
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
            ->selectRaw("COALESCE(NULLIF(members.community_group, ''), '__none__') as grp, sum(shares.total_value) as v")
            ->groupBy('grp')->pluck('v', 'grp');

        $savingsByGroup = DB::table('savings_accounts')
            ->join('members', 'members.id', '=', 'savings_accounts.member_id')
            ->where('savings_accounts.organization_id', $orgId)
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
