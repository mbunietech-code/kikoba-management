<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Share;
use App\Support\Audit;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /** 'regular' here; 'opening' in the subclass. */
    protected string $kind = 'regular';

    /** Route-name prefix + i18n prefix for this ledger. */
    protected string $rp = 'admin.shares';
    protected string $i18n = 'shares';

    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $base = fn () => Share::where('organization_id', $orgId)->kind($this->kind);

        $shares = $base()->with('member')
            ->when($request->q, fn ($q, $s) => $q->whereHas('member', fn ($w) => $w->where('full_name', 'like', "%$s%")))
            ->latest('purchased_at')->paginate(15)->withQueryString();

        $summary = [
            'capital' => (int) $base()->sum('total_value'),
            'quantity' => (int) $base()->sum('quantity'),
            'holders' => $base()->distinct('member_id')->count('member_id'),
        ];
        $members = Member::where('organization_id', $orgId)->where('status', 'active')
            ->orderBy('full_name')->get(['id', 'full_name', 'member_number']);

        return view('admin.shares.index', [
            'shares' => $shares,
            'summary' => $summary,
            'members' => $members,
            'rp' => $this->rp,
            'i18n' => $this->i18n,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price_per_share' => ['required', 'integer', 'min:1'],
            'purchased_at' => ['nullable', 'date'],
        ]);
        $member = Member::findOrFail($data['member_id']);
        $share = Share::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'kind' => $this->kind,
            'quantity' => $data['quantity'],
            'price_per_share' => $data['price_per_share'],
            'total_value' => $data['quantity'] * $data['price_per_share'],
            'purchased_at' => $data['purchased_at'] ?? now()->toDateString(),
            'transaction_reference' => 'TXN-2026-'.str_pad((string) (Share::count() + 1), 6, '0', STR_PAD_LEFT),
            'status' => 'confirmed',
        ]);
        Audit::log($request, 'SHARE_PURCHASE', 'Share', $share->id);

        return back()->with('toast', t($this->i18n.'.recordPurchase').' ✓');
    }

    public function update(Request $request, Share $share)
    {
        abort_unless($share->kind === $this->kind, 404);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'price_per_share' => ['required', 'integer', 'min:1'],
            'purchased_at' => ['nullable', 'date'],
        ]);
        $share->update([...$data, 'total_value' => $data['quantity'] * $data['price_per_share']]);
        Audit::log($request, 'UPDATE_SHARE', 'Share', $share->id);

        return back()->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, Share $share)
    {
        abort_unless($share->kind === $this->kind, 404);
        $share->delete();
        Audit::log($request, 'DELETE_SHARE', 'Share', $share->id);

        return back()->with('toast', t('common.deleted'));
    }
}
