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
            'period' => ['required', 'string', 'max:20'],
        ]);
        $acc = InsuranceAccount::where('organization_id', app('kikoba.org')->id)->findOrFail($data['insurance_account_id']);
        if ($acc->status !== 'active') {
            return back()->with('toast', t('insurance.notActive'))->with('toast_tone', 'error');
        }
        InsuranceContribution::create([
            'organization_id' => $acc->organization_id, 'insurance_account_id' => $acc->id, 'member_id' => $acc->member_id,
            'amount' => $data['amount'], 'period' => $data['period'], 'paid_on' => now()->toDateString(),
            'reference' => next_reference('INS', InsuranceContribution::class, 'reference'),
        ]);
        Audit::log($request, 'INSURANCE_PAYMENT', 'InsuranceContribution', $acc->id);

        return back()->with('toast', t('insurance.recordContribution').' ✓');
    }

    public function fileClaim(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'claim_type' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount_requested' => ['required', 'integer', 'min:1'],
        ]);
        $acc = InsuranceAccount::where('organization_id', app('kikoba.org')->id)->where('member_id', $data['member_id'])->where('status', 'active')->first();
        if (! $acc) {
            return back()->withInput()->with('toast', t('insurance.notActive'))->with('toast_tone', 'error');
        }
        InsuranceClaim::create([
            'organization_id' => $acc->organization_id, 'insurance_account_id' => $acc->id, 'member_id' => $data['member_id'],
            'claim_number' => next_reference('CLM', InsuranceClaim::class, 'claim_number', 5),
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
        // approve / reject an open claim; pay only one that was approved
        $allowedFrom = ['approve' => ['submitted', 'under_review'], 'reject' => ['submitted', 'under_review'], 'pay' => ['approved']];
        if (! in_array($claim->status, $allowedFrom[$data['decision']], true)) {
            return back()->with('toast', t('insurance.claimNotOpen'))->with('toast_tone', 'error');
        }
        if (($data['amount_approved'] ?? 0) > $claim->amount_requested) {
            return back()->with('toast', t('insurance.approvedAboveRequested'))->with('toast_tone', 'error');
        }
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
            return back()->with('toast', t('insurance.paidClaimLocked'))->with('toast_tone', 'error');
        }
        $claim->update(['status' => 'cancelled']);
        Audit::log($request, 'CANCEL_CLAIM', 'InsuranceClaim', $claim->id);

        return back()->with('toast', t('common.deleted'));
    }
}
