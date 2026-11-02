<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalLine;
use App\Models\Member;
use App\Models\ProfitAllocation;
use App\Models\ProfitDistribution;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Share;
use App\Models\Transaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Year-end (or any period) sharing of profit: calculate each member's
 * portion, have it approved, then pay it into savings or in cash.
 */
class ProfitDistributionService
{
    public const BASES = ['shares', 'savings', 'equal'];

    public const PAYOUT_METHODS = ['savings', 'cash'];

    /**
     * Revenue minus expenses posted in the books between the two dates.
     */
    public function booksProfit(string $orgId, Carbon $start, Carbon $end): int
    {
        $lines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.organization_id', $orgId)
            ->where('journal_entries.entry_date', '>=', $start->copy()->startOfDay()->toDateTimeString())
            ->where('journal_entries.entry_date', '<=', $end->copy()->endOfDay()->toDateTimeString())
            ->whereIn('accounts.account_type', ['revenue', 'expense'])
            ->get(['accounts.account_type', 'journal_lines.debit', 'journal_lines.credit']);

        return (int) $lines->sum(fn ($l) => $l->account_type === 'revenue'
            ? $l->credit - $l->debit
            : -($l->debit - $l->credit));
    }

    /**
     * Each member's weight on the chosen basis at the end of the period.
     *
     * @return Collection<string, int> member id => weight
     */
    public function weights(string $orgId, string $basis, Carbon $end): Collection
    {
        $members = Member::where('organization_id', $orgId)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('registration_date')->orWhere('registration_date', '<=', $end->copy()->endOfDay()->toDateTimeString()))
            ->pluck('id');

        $weights = match ($basis) {
            'shares' => Share::where('organization_id', $orgId)->whereIn('member_id', $members)
                ->where('purchased_at', '<=', $end->copy()->endOfDay()->toDateTimeString())
                ->selectRaw('member_id, sum(total_value) as w')->groupBy('member_id')->pluck('w', 'member_id'),
            'savings' => SavingsTransaction::where('organization_id', $orgId)->whereIn('member_id', $members)
                ->where('created_at', '<=', $end->copy()->endOfDay())
                ->orderBy('created_at')->get(['member_id', 'balance_after'])
                ->groupBy('member_id')->map(fn ($rows) => $rows->last()->balance_after),
            default => $members->mapWithKeys(fn ($id) => [$id => 1]),
        };

        return collect($weights)->map(fn ($w) => (int) $w)->filter(fn (int $w) => $w > 0);
    }

    /**
     * Split $amount by weight using the largest-remainder method so the parts add up exactly.
     *
     * @param  Collection<string, int>  $weights
     * @return Collection<string, array{amount: int, percentage: float}>
     */
    public function split(int $amount, Collection $weights): Collection
    {
        $total = $weights->sum();
        if ($total <= 0 || $amount <= 0) {
            return collect();
        }

        $parts = $weights->map(fn (int $w) => ['exact' => $amount * $w / $total, 'percentage' => round($w * 100 / $total, 4)]);
        $floors = $parts->map(fn ($p) => (int) floor($p['exact']));
        $left = $amount - $floors->sum();
        $bonus = $parts->sortByDesc(fn ($p) => $p['exact'] - floor($p['exact']))->keys()->take($left)->flip();

        return $parts->map(fn ($p, $id) => [
            'amount' => $floors[$id] + ($bonus->has($id) ? 1 : 0),
            'percentage' => $p['percentage'],
        ]);
    }

    /**
     * @param  array{period_start: string, period_end: string, total_profit: int, reserve_percent: float|int|string, basis: string}  $data
     */
    public function calculate(Request $request, string $orgId, array $data): ProfitDistribution
    {
        $start = Carbon::parse($data['period_start'])->startOfDay();
        $end = Carbon::parse($data['period_end'])->endOfDay();

        $overlap = ProfitDistribution::where('organization_id', $orgId)
            ->whereIn('status', ['approved', 'distributed'])
            ->where('period_start', '<=', $end->toDateTimeString())
            ->where('period_end', '>=', $start->toDateString())
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['period_start' => t('profit.overlaps')]);
        }

        $total = (int) $data['total_profit'];
        $reserved = (int) round($total * (float) $data['reserve_percent'] / 100);
        $distributable = $total - $reserved;
        $shares = $this->split($distributable, $this->weights($orgId, $data['basis'], $end));
        if ($shares->isEmpty()) {
            throw ValidationException::withMessages(['basis' => t('profit.nobodyQualifies')]);
        }

        return DB::transaction(function () use ($request, $orgId, $data, $start, $end, $total, $reserved, $distributable, $shares) {
            $distribution = ProfitDistribution::create([
                'organization_id' => $orgId,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'total_profit' => $total,
                'reserved_amount' => $reserved,
                'distributable_profit' => $distributable,
                'basis' => $data['basis'],
                'status' => 'calculated',
            ]);
            foreach ($shares as $memberId => $share) {
                ProfitAllocation::create([
                    'profit_distribution_id' => $distribution->id,
                    'member_id' => $memberId,
                    'basis' => $data['basis'],
                    'percentage' => $share['percentage'],
                    'amount' => $share['amount'],
                    'status' => 'pending',
                ]);
            }
            Audit::log($request, 'CALCULATE_PROFIT', 'ProfitDistribution', $distribution->id, null, ['total' => $total, 'basis' => $data['basis']]);

            return $distribution;
        });
    }

    public function approve(Request $request, ProfitDistribution $distribution): void
    {
        if ($distribution->status !== 'calculated') {
            throw ValidationException::withMessages(['status' => t('profit.notCalculated')]);
        }
        $distribution->update(['status' => 'approved']);
        Audit::log($request, 'APPROVE_PROFIT', 'ProfitDistribution', $distribution->id);
    }

    /**
     * Pay every member's share into their savings account or record it as paid in cash.
     * Books: Dr Retained earnings / Cr Member savings (or Cr Cash).
     */
    public function payOut(Request $request, ProfitDistribution $distribution, string $method): void
    {
        DB::transaction(function () use ($request, $distribution, $method) {
            $distribution = ProfitDistribution::lockForUpdate()->findOrFail($distribution->id);
            if ($distribution->status !== 'approved') {
                throw ValidationException::withMessages(['status' => t('profit.notApproved')]);
            }

            $orgId = $distribution->organization_id;
            $this->ensureAccount($orgId, '3000', 'Retained Earnings', 'equity');
            $this->ensureAccount($orgId, $method === 'savings' ? '1100' : '1000', $method === 'savings' ? 'Member Savings' : 'Cash', $method === 'savings' ? 'liability' : 'asset');
            $label = 'Profit share '.$distribution->period_start->format('d/m/Y').' – '.$distribution->period_end->format('d/m/Y');

            $paid = 0;
            foreach ($distribution->allocations()->with('member')->where('amount', '>', 0)->get() as $allocation) {
                $reference = next_reference('TXN', Transaction::class, 'transaction_reference');
                $txn = Transaction::create([
                    'organization_id' => $orgId,
                    'member_id' => $allocation->member_id,
                    'transaction_reference' => $reference,
                    'type' => 'PROFIT_DIVIDEND',
                    'amount' => $allocation->amount,
                    'status' => 'successful',
                    'description' => $label.($method === 'cash' ? ' (cash)' : ''),
                    'created_by' => $request->user()?->name,
                ]);

                if ($method === 'savings') {
                    $account = SavingsAccount::lockForUpdate()->find(SavingsAccount::openFor($allocation->member)->id);
                    SavingsTransaction::create([
                        'organization_id' => $orgId,
                        'savings_account_id' => $account->id,
                        'member_id' => $allocation->member_id,
                        'transaction_id' => $txn->id,
                        'type' => 'dividend',
                        'amount' => $allocation->amount,
                        'balance_before' => $account->balance,
                        'balance_after' => $account->balance + $allocation->amount,
                        'reference' => $reference,
                    ]);
                    $account->increment('balance', $allocation->amount);
                }

                $allocation->update(['status' => 'paid']);
                $paid += $allocation->amount;
            }

            if ($paid > 0) {
                app(AccountingService::class)->post($orgId, $label, $label, [
                    ['3000', $paid, 0],
                    [$method === 'savings' ? '1100' : '1000', 0, $paid],
                ]);
            }

            $distribution->update(['status' => 'distributed', 'distribution_date' => now()->toDateString()]);
            Audit::log($request, 'DISTRIBUTE_PROFIT', 'ProfitDistribution', $distribution->id, null, ['method' => $method, 'amount' => $paid]);
        });
    }

    private function ensureAccount(string $orgId, string $code, string $name, string $type): void
    {
        Account::firstOrCreate(
            ['organization_id' => $orgId, 'account_code' => $code],
            ['name' => $name, 'account_type' => $type, 'balance' => 0],
        );
    }
}
