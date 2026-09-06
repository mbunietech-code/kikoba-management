<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Financial records are never hard-deleted — a correcting reversal is posted
 * so the audit trail and ledger stay intact (SDD §39).
 */
class ReversalService
{
    public function reverseTransaction(Request $request, Transaction $txn, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($request, $txn, $reason) {
            if ($txn->status === 'reversed') {
                throw new RuntimeException('This transaction has already been reversed.');
            }

            $reference = 'TXN-'.now()->format('Y').'-'.str_pad((string) (Transaction::count() + 1), 6, '0', STR_PAD_LEFT);

            $reversal = Transaction::create([
                'organization_id' => $txn->organization_id,
                'member_id' => $txn->member_id,
                'transaction_reference' => $reference,
                'type' => 'REVERSAL',
                'amount' => -$txn->amount,
                'status' => 'successful',
                'description' => 'Reversal of '.$txn->transaction_reference.($reason ? " — {$reason}" : ''),
                'reversal_of' => $txn->id,
                'created_by' => $request->user()?->name,
            ]);

            $txn->update(['status' => 'reversed']);

            // undo linked savings movement if any
            $savingsTxn = SavingsTransaction::where('transaction_id', $txn->id)->first();
            if ($savingsTxn) {
                $account = SavingsAccount::lockForUpdate()->find($savingsTxn->savings_account_id);
                $delta = $savingsTxn->type === 'deposit' ? -$savingsTxn->amount : $savingsTxn->amount;
                $newBalance = $account->balance + $delta;
                SavingsTransaction::create([
                    'organization_id' => $account->organization_id,
                    'savings_account_id' => $account->id,
                    'member_id' => $account->member_id,
                    'transaction_id' => $reversal->id,
                    'type' => 'reversal',
                    'amount' => $savingsTxn->amount,
                    'balance_before' => $account->balance,
                    'balance_after' => $newBalance,
                    'reference' => $reference,
                    'reversal_of' => $savingsTxn->id,
                ]);
                $account->update(['balance' => $newBalance]);
            }

            Audit::log($request, 'REVERSE_TRANSACTION', 'Transaction', $txn->id, ['status' => 'successful'], ['status' => 'reversed']);

            return $reversal;
        });
    }

    public function reversePayment(Request $request, Payment $payment, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($request, $payment, $reason) {
            if ($payment->status === 'reversed') {
                throw new RuntimeException('This payment has already been reversed.');
            }
            $payment->update(['status' => 'reversed']);
            Audit::log($request, 'REVERSE_PAYMENT', 'Payment', $payment->id, ['status' => $payment->getOriginal('status')], ['status' => 'reversed', 'reason' => $reason]);

            return $payment->fresh();
        });
    }
}
