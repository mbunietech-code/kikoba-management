<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\AccountingService;
use App\Support\Audit;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $tab = $request->get('tab', 'coa');

        $accounts = Account::where('organization_id', $orgId)->orderBy('account_code')->get();
        $journal = JournalEntry::where('organization_id', $orgId)->with('lines.account')->latest('entry_date')
            ->paginate(20)->withQueryString();

        $totalDebit = (int) JournalLine::whereHas('entry', fn ($q) => $q->where('organization_id', $orgId))->sum('debit');
        $totalCredit = (int) JournalLine::whereHas('entry', fn ($q) => $q->where('organization_id', $orgId))->sum('credit');

        return view('admin.accounting.index', compact('accounts', 'journal', 'tab', 'totalDebit', 'totalCredit'));
    }

    public function showJournal(JournalEntry $entry)
    {
        $entry->load('lines.account');

        return view('admin.accounting.journal', compact('entry'));
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
        ]);
        Account::create([...$data, 'organization_id' => app('kikoba.org')->id, 'balance' => 0]);
        Audit::log($request, 'CREATE_ACCOUNT', 'Account', null);

        return back()->with('toast', t('common.save').' ✓');
    }

    public function updateAccount(Request $request, Account $account)
    {
        $account->update($request->validate([
            'name' => ['required', 'string'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'status' => ['required', 'in:active,inactive'],
        ]));
        Audit::log($request, 'UPDATE_ACCOUNT', 'Account', $account->id);

        return back()->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroyAccount(Request $request, Account $account)
    {
        if (JournalLine::where('account_id', $account->id)->exists()) {
            return back()->with('toast', 'Account has entries');
        }
        $account->delete();
        Audit::log($request, 'DELETE_ACCOUNT', 'Account', $account->id);

        return back()->with('toast', t('common.deleted'));
    }

    public function reverseJournal(Request $request, JournalEntry $entry)
    {
        $entry->load('lines.account');
        $lines = $entry->lines->map(fn ($l) => [$l->account->account_code, $l->credit, $l->debit])->all();
        app(AccountingService::class)->post($entry->organization_id, $entry->reference.'-REV', 'Reversal of '.$entry->reference, $lines, $entry->transaction_id);
        Audit::log($request, 'REVERSE_JOURNAL', 'JournalEntry', $entry->id);

        return back()->with('toast', t('common.reverse').' ✓');
    }
}
