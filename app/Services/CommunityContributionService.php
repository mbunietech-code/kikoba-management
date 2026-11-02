<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CommunityContribution;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jamii and Dharura contributions: standard amounts, recording payments into
 * the two group funds (with their books entries) and reversing mistakes.
 */
class CommunityContributionService
{
    /** Ledger account holding each fund (money the group holds for members' welfare). */
    public const FUND_ACCOUNTS = [
        'jamii' => ['2200', 'Jamii Fund'],
        'dharura' => ['2300', 'Emergency (Dharura) Fund'],
    ];

    private const CASH_ACCOUNT = '1000';

    /**
     * The group's standard monthly amount per fund.
     *
     * @return array{jamii: int, dharura: int}
     */
    public function rates(string $orgId): array
    {
        $stored = Setting::where('organization_id', $orgId)
            ->whereIn('key', ['community_jamii_amount', 'community_dharura_amount'])->pluck('value', 'key');

        return [
            'jamii' => (int) ($stored['community_jamii_amount'] ?? CommunityContribution::DEFAULT_AMOUNT),
            'dharura' => (int) ($stored['community_dharura_amount'] ?? CommunityContribution::DEFAULT_AMOUNT),
        ];
    }

    /**
     * @param  array{jamii: int, dharura: int}  $rates
     */
    public function updateRates(Request $request, string $orgId, array $rates): void
    {
        $before = $this->rates($orgId);
        foreach ($rates as $fund => $amount) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => "community_{$fund}_amount"],
                ['value' => (string) $amount, 'updated_by' => $request->user()->id],
            );
        }
        Audit::log($request, 'UPDATE_COMMUNITY_RATES', 'Setting', null, $before, $rates);
    }

    /**
     * Record one member's payment towards one or both funds for a month.
     *
     * @param  array{jamii: int, dharura: int}  $amounts  0 skips that fund
     * @return Collection<int, CommunityContribution>
     */
    public function record(Request $request, Member $member, string $period, string $paidOn, array $amounts, ?string $reference): Collection
    {
        $amounts = array_filter($amounts, fn (int $a) => $a > 0);
        if ($amounts === []) {
            throw ValidationException::withMessages(['jamii_amount' => t('community.enterAnAmount')]);
        }

        return DB::transaction(function () use ($request, $member, $period, $paidOn, $amounts, $reference) {
            $orgId = $member->organization_id;
            $this->ensureAccounts($orgId);
            $recorded = collect();
            $lines = [[self::CASH_ACCOUNT, array_sum($amounts), 0]];

            foreach ($amounts as $fund => $amount) {
                $txnRef = next_reference('TXN', Transaction::class, 'transaction_reference');
                $txn = Transaction::create([
                    'organization_id' => $orgId,
                    'member_id' => $member->id,
                    'transaction_reference' => $txnRef,
                    'type' => 'COMMUNITY_'.strtoupper($fund),
                    'amount' => $amount,
                    'status' => 'successful',
                    'description' => ucfirst($fund).' '.$period,
                    'created_by' => $request->user()?->name,
                ]);
                $contribution = CommunityContribution::create([
                    'organization_id' => $orgId,
                    'member_id' => $member->id,
                    'fund' => $fund,
                    'amount' => $amount,
                    'period' => $period,
                    'paid_on' => $paidOn,
                    'reference' => $reference ?: $txnRef,
                    'transaction_id' => $txn->id,
                    'status' => 'recorded',
                    'recorded_by' => $request->user()?->name,
                ]);
                $lines[] = [self::FUND_ACCOUNTS[$fund][0], 0, $amount];
                $recorded->push($contribution);
                Audit::log($request, 'RECORD_COMMUNITY_CONTRIBUTION', 'CommunityContribution', $contribution->id, null, ['fund' => $fund, 'amount' => $amount, 'period' => $period]);
            }

            app(AccountingService::class)->post($orgId, $member->member_number, "Jamii/Dharura {$period} — {$member->full_name}", $lines, $recorded->first()->transaction_id);

            return $recorded;
        });
    }

    /**
     * Undo a contribution recorded by mistake: the fund and cash go back down
     * and the original stays on record as reversed.
     */
    public function reverse(Request $request, CommunityContribution $contribution, string $reason): void
    {
        DB::transaction(function () use ($request, $contribution, $reason) {
            $contribution = CommunityContribution::lockForUpdate()->findOrFail($contribution->id);
            if ($contribution->status !== 'recorded') {
                throw ValidationException::withMessages(['reason' => t('transactions.reversal.already')]);
            }

            $reference = next_reference('TXN', Transaction::class, 'transaction_reference');
            $reversal = Transaction::create([
                'organization_id' => $contribution->organization_id,
                'member_id' => $contribution->member_id,
                'transaction_reference' => $reference,
                'type' => 'REVERSAL',
                'amount' => -$contribution->amount,
                'status' => 'successful',
                'description' => 'Reversal of '.ucfirst($contribution->fund).' '.$contribution->period." — {$reason}",
                'reversal_of' => $contribution->transaction_id,
                'created_by' => $request->user()?->name,
            ]);
            Transaction::whereKey($contribution->transaction_id)->update(['status' => 'reversed']);

            app(AccountingService::class)->post($contribution->organization_id, $reference, 'Reversal of '.ucfirst($contribution->fund).' '.$contribution->period, [
                [self::FUND_ACCOUNTS[$contribution->fund][0], $contribution->amount, 0],
                [self::CASH_ACCOUNT, 0, $contribution->amount],
            ], $reversal->id);

            $contribution->update(['status' => 'reversed', 'reversal_reason' => $reason]);
            Audit::log($request, 'REVERSE_COMMUNITY_CONTRIBUTION', 'CommunityContribution', $contribution->id, ['status' => 'recorded'], ['status' => 'reversed', 'reason' => $reason]);
        });
    }

    /**
     * Money in each fund: everything contributed that has not been reversed.
     *
     * @return array{jamii: int, dharura: int}
     */
    public function fundTotals(string $orgId): array
    {
        $sums = CommunityContribution::where('organization_id', $orgId)->counted()
            ->selectRaw('fund, sum(amount) as total')->groupBy('fund')->pluck('total', 'fund');

        return ['jamii' => (int) ($sums['jamii'] ?? 0), 'dharura' => (int) ($sums['dharura'] ?? 0)];
    }

    private function ensureAccounts(string $orgId): void
    {
        Account::firstOrCreate(['organization_id' => $orgId, 'account_code' => self::CASH_ACCOUNT], ['name' => 'Cash', 'account_type' => 'asset', 'balance' => 0]);
        foreach (self::FUND_ACCOUNTS as [$code, $name]) {
            Account::firstOrCreate(['organization_id' => $orgId, 'account_code' => $code], ['name' => $name, 'account_type' => 'liability', 'balance' => 0]);
        }
    }
}
