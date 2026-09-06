<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\InsuranceContribution;
use App\Support\Audit;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $tab = $request->get('tab', 'accounts');

        $accounts = InsuranceAccount::where('organization_id', $orgId)->with('member')
            ->withSum('contributions as total_contributed', 'amount')->paginate(15, ['*'], 'a')->withQueryString();
        $claims = InsuranceClaim::where('organization_id', $orgId)->with('member')->latest('submitted_at')
            ->paginate(15, ['*'], 'c')->withQueryString();

        $summary = [
            'contributions' => (int) InsuranceContribution::where('organization_id', $orgId)->sum('amount'),
            'claimsPaid' => (int) InsuranceClaim::where('organization_id', $orgId)->where('status', 'paid')->sum('amount_approved'),
            'covered' => InsuranceAccount::where('organization_id', $orgId)->where('status', 'active')->count(),
        ];

        return view('admin.insurance.index', compact('accounts', 'claims', 'summary', 'tab'));
    }

    public function contribute(Request $request)
    {
        $data = $request->validate([
            'insurance_account_id' => ['required', 'exists:insurance_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'string'],
        ]);
        $acc = InsuranceAccount::findOrFail($data['insurance_account_id']);
        InsuranceContribution::create([
            'organization_id' => $acc->organization_id, 'insurance_account_id' => $acc->id, 'member_id' => $acc->member_id,
            'amount' => $data['amount'], 'period' => $data['period'], 'paid_on' => now()->toDateString(),
            'reference' => 'TXN-2026-'.str_pad((string) rand(8000, 9999), 6, '0', STR_PAD_LEFT),
        ]);
        Audit::log($request, 'INSURANCE_PAYMENT', 'InsuranceContribution', $acc->id);

        return back()->with('toast', t('insurance.recordContribution').' ✓');
    }

    public function fileClaim(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'claim_type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'amount_requested' => ['required', 'integer', 'min:1'],
        ]);
        $acc = InsuranceAccount::where('member_id', $data['member_id'])->firstOrFail();
        InsuranceClaim::create([
            'organization_id' => $acc->organization_id, 'insurance_account_id' => $acc->id, 'member_id' => $data['member_id'],
            'claim_number' => 'CLM-'.str_pad((string) (InsuranceClaim::count() + 1), 5, '0', STR_PAD_LEFT),
            'claim_type' => $data['claim_type'], 'description' => $data['description'] ?? null,
            'amount_requested' => $data['amount_requested'], 'status' => 'submitted', 'submitted_at' => now(),
        ]);
        Audit::log($request, 'FILE_CLAIM', 'InsuranceClaim', $acc->id);

        return back()->with('toast', t('insurance.fileClaim').' ✓');
    }

    public function decideClaim(Request $request, InsuranceClaim $claim)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,pay'],
            'amount_approved' => ['nullable', 'integer', 'min:0'],
        ]);
        $map = ['approve' => 'approved', 'reject' => 'rejected', 'pay' => 'paid'];
        $claim->update([
            'status' => $map[$data['decision']],
            'amount_approved' => $data['amount_approved'] ?? $claim->amount_approved ?: $claim->amount_requested,
            'approved_at' => in_array($data['decision'], ['approve', 'pay']) ? now() : $claim->approved_at,
            'paid_at' => $data['decision'] === 'pay' ? now() : $claim->paid_at,
        ]);
        Audit::log($request, strtoupper($data['decision']).'_CLAIM', 'InsuranceClaim', $claim->id);

        return back()->with('toast', t('common.save').' ✓');
    }

    public function destroyAccount(Request $request, InsuranceAccount $account)
    {
        $account->update(['status' => 'cancelled']);
        Audit::log($request, 'CANCEL_INSURANCE', 'InsuranceAccount', $account->id);

        return back()->with('toast', t('common.deleted'));
    }

    public function destroyClaim(Request $request, InsuranceClaim $claim)
    {
        if ($claim->status === 'paid') {
            return back()->with('toast', 'Paid claim cannot be removed');
        }
        $claim->update(['status' => 'cancelled']);
        Audit::log($request, 'CANCEL_CLAIM', 'InsuranceClaim', $claim->id);

        return back()->with('toast', t('common.deleted'));
    }
}
