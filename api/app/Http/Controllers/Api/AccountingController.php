<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\AccountingService;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class AccountingController extends ApiController
{
    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
        ]);
        $account = Account::create([...$data, 'organization_id' => $this->orgId($request), 'balance' => 0]);
        Audit::log($request, 'CREATE_ACCOUNT', 'Account', $account->id);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($account))->resolve(), 'Account created');
    }

    public function updateAccount(Request $request, string $id)
    {
        return $this->crudUpdate($request, $this->find($request, Account::class, $id), [
            'name' => ['sometimes', 'string'],
            'account_type' => ['sometimes', 'in:asset,liability,equity,revenue,expense'],
            'status' => ['sometimes', 'in:active,inactive'],
        ], 'Account');
    }

    public function destroyAccount(Request $request, string $id)
    {
        $account = $this->find($request, Account::class, $id);
        if (JournalLine::where('account_id', $account->id)->exists()) {
            return ApiResponse::error('IN_USE', 'This account has journal entries and cannot be deleted.', 422);
        }

        return $this->crudDestroy($request, $account, 'Account');
    }

    public function reverseJournal(Request $request, string $id)
    {
        $entry = JournalEntry::where('organization_id', $this->orgId($request))->with('lines.account')->findOrFail($id);
        $lines = $entry->lines->map(fn ($l) => [$l->account->account_code, $l->credit, $l->debit])->all();
        $reversal = app(AccountingService::class)->post(
            $entry->organization_id,
            $entry->reference.'-REV',
            'Reversal of '.$entry->reference,
            $lines,
            $entry->transaction_id,
        );
        Audit::log($request, 'REVERSE_JOURNAL', 'JournalEntry', $entry->id);

        return $this->item($reversal->load('lines.account'), fn ($j) => $this->row($j, true));
    }

    public function accounts(Request $request)
    {
        return $this->items(
            Account::where('organization_id', $this->orgId($request))->orderBy('account_code')->get(),
            fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->account_code,
                'name' => $a->name,
                'type' => $a->account_type,
                'balance' => $a->balance,
            ],
        );
    }

    public function journal(Request $request)
    {
        $q = JournalEntry::where('organization_id', $this->orgId($request))
            ->with('lines.account:id,account_code,name')
            ->latest('entry_date');

        return $this->paginate($q, $request, fn (JournalEntry $j) => $this->row($j));
    }

    public function showJournal(Request $request, string $id)
    {
        $j = JournalEntry::where('organization_id', $this->orgId($request))
            ->with('lines.account:id,account_code,name')
            ->findOrFail($id);

        return $this->item($j, fn ($j) => $this->row($j, true));
    }

    public function trialBalance(Request $request)
    {
        $accounts = Account::where('organization_id', $this->orgId($request))->orderBy('account_code')->get();
        $rows = $accounts->map(function (Account $a) {
            $isDebit = in_array($a->account_type, ['asset', 'expense']);

            return [
                'code' => $a->account_code,
                'name' => $a->name,
                'type' => $a->account_type,
                'debit' => $isDebit ? $a->balance : 0,
                'credit' => $isDebit ? 0 : $a->balance,
            ];
        });

        return ApiResponse::ok([
            'rows' => $rows,
            'totalDebit' => (int) $rows->sum('debit'),
            'totalCredit' => (int) $rows->sum('credit'),
        ]);
    }

    private function row(JournalEntry $j, bool $withLines = false): array
    {
        $amount = (int) $j->lines->sum('debit');
        $data = [
            'id' => $j->id,
            'reference' => $j->reference,
            'description' => $j->description,
            'entryDate' => $j->entry_date?->toDateString(),
            'postedBy' => $j->posted_by,
            'transactionRef' => $j->transaction_id,
            'amount' => $amount,
        ];
        if ($withLines) {
            $data['lines'] = $j->lines->map(fn ($l) => [
                'accountCode' => $l->account?->account_code,
                'accountName' => $l->account?->name,
                'debit' => $l->debit,
                'credit' => $l->credit,
            ]);
        }

        return $data;
    }
}
