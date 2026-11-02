<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Financial records are never hard-deleted — a correcting reversal is posted
 * so the audit trail and ledger stay intact (SDD §39).
 */
class ReversalService
{
    /**
     * Only savings movements can be undone here: their effect is fully known
     * (one balance and one journal). Loan disbursements and repayments also
     * change the loan, its schedule and allocations, so reversing just the
     * transaction row would leave the loan and the books out of step.
     */
    private const REVERSIBLE_TYPES = ['SAVINGS_DEPOSIT', 'SAVINGS_WITHDRAWAL'];

    public function reverseTransaction(Request $request, Transaction $txn, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($request, $txn, $reason) {
            $txn = Transaction::lockForUpdate()->findOrFail($txn->id);

            if ($txn->status === 'reversed') {
                $this->refuse(t('transactions.reversal.already'));
            }
            if (! in_array($txn->type, self::REVERSIBLE_TYPES, true)) {
                $this->refuse(t('transactions.reversal.notAllowed'));
            }

            $savingsTxn = SavingsTransaction::where('transaction_id', $txn->id)->first();
            $account = $savingsTxn ? SavingsAccount::lockForUpdate()->find($savingsTxn->savings_account_id) : null;
            $delta = $savingsTxn ? ($savingsTxn->type === 'deposit' ? -$savingsTxn->amount : $savingsTxn->amount) : 0;
            if ($account && $account->balance + $delta < 0) {
                $this->refuse(t('transactions.reversal.wouldOverdraw', ['balance' => money($account->balance)]));
            }

            $reference = next_reference('TXN', Transaction::class, 'transaction_reference');

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

            // undo linked savings movement
            if ($savingsTxn && $account) {
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

            // mirror every journal the original posted, so cash and savings in the books follow the balances
            $accounting = app(AccountingService::class);
            JournalEntry::where('transaction_id', $txn->id)->with('lines.account')->get()
                ->each(fn (JournalEntry $entry) => $accounting->post(
                    $txn->organization_id,
                    $reference,
                    'Reversal of '.$entry->reference,
                    $entry->lines->map(fn ($line) => [$line->account->account_code, (int) $line->credit, (int) $line->debit])->all(),
                    $reversal->id,
                ));

            Audit::log($request, 'REVERSE_TRANSACTION', 'Transaction', $txn->id, ['status' => 'successful'], ['status' => 'reversed', 'reason' => $reason]);

            return $reversal;
        });
    }

    private function refuse(string $message): never
    {
        throw ValidationException::withMessages(['reversal' => $message]);
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
