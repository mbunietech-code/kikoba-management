<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AccountingController extends ApiController
{
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
