<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountingService
{
    /**
     * Post a balanced double-entry journal.
     *
     * @param  array<int, array{0:string,1:int,2:int}>  $lines  [accountCode, debit, credit]
     */
    public function post(string $orgId, string $reference, string $description, array $lines, ?string $transactionId = null): JournalEntry
    {
        $debit = array_sum(array_column($lines, 1));
        $credit = array_sum(array_column($lines, 2));

        if ($debit !== $credit) {
            throw new RuntimeException("Unbalanced journal: debit {$debit} != credit {$credit}");
        }

        return DB::transaction(function () use ($orgId, $reference, $description, $lines, $transactionId) {
            $entry = JournalEntry::create([
                'organization_id' => $orgId,
                'transaction_id' => $transactionId,
                'reference' => 'JE-'.now()->format('Y').'-'.str_pad((string) (JournalEntry::count() + 1), 5, '0', STR_PAD_LEFT),
                'description' => $description,
                'entry_date' => now()->toDateString(),
                'posted_by' => 'System',
            ]);

            foreach ($lines as [$code, $dr, $cr]) {
                $account = Account::firstOrCreate(
                    ['organization_id' => $orgId, 'account_code' => $code],
                    ['name' => $code, 'account_type' => 'asset'],
                );

                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => $dr,
                    'credit' => $cr,
                ]);

                // maintain a running balance (sign by normal balance side)
                $normalDebit = in_array($account->account_type, ['asset', 'expense']);
                $delta = $normalDebit ? $dr - $cr : $cr - $dr;
                $account->increment('balance', $delta);
            }

            return $entry;
        });
    }
}
