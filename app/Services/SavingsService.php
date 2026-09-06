<?php

namespace App\Services;

use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsService
{
    public function deposit(Request $request, array $data): SavingsTransaction
    {
        return $this->move($request, $data, 'deposit');
    }

    public function withdraw(Request $request, array $data): SavingsTransaction
    {
        return $this->move($request, $data, 'withdrawal');
    }

    private function move(Request $request, array $data, string $type): SavingsTransaction
    {
        return DB::transaction(function () use ($request, $data, $type) {
            /** @var SavingsAccount $account */
            $account = SavingsAccount::lockForUpdate()->findOrFail($data['account_id']);

            if ($type === 'withdrawal' && $account->balance < $data['amount']) {
                throw ValidationException::withMessages([
                    'amount' => 'Withdrawal exceeds the available balance.',
                ]);
            }

            $before = $account->balance;
            $after = $type === 'deposit' ? $before + $data['amount'] : $before - $data['amount'];

            $reference = 'TXN-2026-'.str_pad((string) (Transaction::count() + 1), 6, '0', STR_PAD_LEFT);

            $txn = Transaction::create([
                'organization_id' => $account->organization_id,
                'member_id' => $account->member_id,
                'transaction_reference' => $reference,
                'type' => $type === 'deposit' ? 'SAVINGS_DEPOSIT' : 'SAVINGS_WITHDRAWAL',
                'amount' => $data['amount'],
                'status' => 'successful',
                'description' => ucfirst($type).' — savings',
                'created_by' => $request->user()?->name,
            ]);

            $savingsTxn = SavingsTransaction::create([
                'organization_id' => $account->organization_id,
                'savings_account_id' => $account->id,
                'member_id' => $account->member_id,
                'transaction_id' => $txn->id,
                'type' => $type,
                'amount' => $data['amount'],
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $data['reference'] ?? $reference,
                'created_at' => $data['date'] ?? now(),
                'updated_at' => now(),
            ]);

            $account->update(['balance' => $after]);

            app(AccountingService::class)->post(
                $account->organization_id,
                $reference,
                ucfirst($type).' — savings',
                $type === 'deposit'
                    ? [['1000', $data['amount'], 0], ['1100', 0, $data['amount']]]
                    : [['1100', $data['amount'], 0], ['1000', 0, $data['amount']]],
                $txn->id,
            );

            Audit::log($request, $type === 'deposit' ? 'RECORD_DEPOSIT' : 'RECORD_WITHDRAWAL', 'SavingsTransaction', $savingsTxn->id, null, ['amount' => $data['amount']]);

            return $savingsTxn;
        });
    }
}
