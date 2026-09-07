<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Audit;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::where('organization_id', app('kikoba.org')->id)
            ->withSum('shares as shares_value', 'total_value')
            ->with('savingsAccount:id,member_id,balance')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->gender, fn ($q, $g) => $q->where('gender', $g))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")))
            ->orderBy('member_number')
            ->paginate(15)->withQueryString();

        return view('admin.members.index', compact('members'));
    }

    public function create()
    {
        return view('admin.members.form', ['member' => new Member()]);
    }

    public function edit(Member $member)
    {
        return view('admin.members.form', compact('member'));
    }

    public function show(Request $request, Member $member)
    {
        $member->load([
            'shares', 'savingsAccount', 'loans.product', 'insuranceAccount',
            'guaranteeing.loan', 'transactions' => fn ($q) => $q->latest()->limit(40),
            'savingsTransactions' => fn ($q) => $q->latest('created_at')->limit(40),
        ]);
        $tab = $request->get('tab', 'profile');

        return view('admin.members.show', compact('member', 'tab'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $orgId = app('kikoba.org')->id;
        $data['organization_id'] = $orgId;
        $data['member_number'] = 'MBR-'.str_pad((string) (Member::where('organization_id', $orgId)->count() + 1), 6, '0', STR_PAD_LEFT);
        $data['registration_date'] = now()->toDateString();
        $data['avatar_color'] = '#'.substr(md5($data['full_name']), 0, 6);

        $member = Member::create($data);
        Audit::log($request, 'CREATE_MEMBER', 'Member', $member->id);

        return redirect()->route('admin.members.show', $member)->with('toast', t('members.addMember').' ✓');
    }

    public function update(Request $request, Member $member)
    {
        $member->update($this->validated($request));
        Audit::log($request, 'UPDATE_MEMBER', 'Member', $member->id);

        return redirect()->route('admin.members.show', $member)->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, Member $member)
    {
        // SDD §39 — members tied to financial records are never removed; they
        // are deactivated instead so loans / savings / transactions stay intact.
        $hasRecords = $member->loans()->exists()
            || $member->shares()->exists()
            || (int) $member->savingsAccount?->balance > 0
            || $member->projectInvestments()->exists()
            || $member->insuranceAccount()->exists();

        if ($hasRecords) {
            $member->update(['status' => 'inactive']);
            Audit::log($request, 'DEACTIVATE_MEMBER', 'Member', $member->id, null, ['status' => 'inactive']);

            return redirect()->route('admin.members.show', $member)
                ->with('toast', t('members.deactivatedInstead'));
        }

        $member->delete();
        Audit::log($request, 'DELETE_MEMBER', 'Member', $member->id);

        return redirect()->route('admin.members.index')->with('toast', t('common.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'next_of_kin' => ['nullable', 'string'],
            'next_of_kin_phone' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,active,suspended,inactive,deceased'],
        ]);
    }
}
