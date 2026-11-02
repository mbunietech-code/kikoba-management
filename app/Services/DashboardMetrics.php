<?php

namespace App\Services;

use App\Models\JournalLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Month-by-month figures for dashboard charts, read from the posted ledger
 * so the charts always agree with the books.
 */
class DashboardMetrics
{
    /** Cash-in-hand and bank accounts. */
    private const CASH_ACCOUNTS = ['1000', '1010'];

    private const SAVINGS_ACCOUNT = '1100';

    private const LOANS_ACCOUNT = '1300';

    /**
     * Money received into and paid out of cash/bank per month.
     *
     * @return array{labels: array<int, string>, inflow: array<int, int>, outflow: array<int, int>, period: string}
     */
    public function cashFlow(string $orgId, int $months = 6): array
    {
        $buckets = $this->monthlyTotals($orgId, self::CASH_ACCOUNTS, $months);

        return [
            'labels' => $buckets->pluck('label')->all(),
            'inflow' => $buckets->pluck('debit')->all(),
            'outflow' => $buckets->pluck('credit')->all(),
            'period' => $this->periodLabel($buckets),
        ];
    }

    /**
     * Net new member savings vs. new loans issued per month.
     *
     * @return array{labels: array<int, string>, savings: array<int, int>, loans: array<int, int>, period: string}
     */
    public function savingsVsLoans(string $orgId, int $months = 6): array
    {
        $savings = $this->monthlyTotals($orgId, [self::SAVINGS_ACCOUNT], $months);
        $loans = $this->monthlyTotals($orgId, [self::LOANS_ACCOUNT], $months);

        return [
            'labels' => $savings->pluck('label')->all(),
            'savings' => $savings->map(fn (array $m) => $m['credit'] - $m['debit'])->all(),
            'loans' => $loans->pluck('debit')->all(),
            'period' => $this->periodLabel($savings),
        ];
    }

    /**
     * @param  array<int, string>  $accountCodes
     * @return Collection<int, array{key: string, label: string, debit: int, credit: int}>
     */
    private function monthlyTotals(string $orgId, array $accountCodes, int $months): Collection
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $buckets = collect(range(0, $months - 1))->mapWithKeys(function (int $i) use ($start) {
            $month = $start->copy()->addMonths($i);

            return [$month->format('Y-m') => ['key' => $month->format('Y-m'), 'label' => $month->translatedFormat('M'), 'debit' => 0, 'credit' => 0]];
        });

        $lines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.organization_id', $orgId)
            ->whereIn('accounts.account_code', $accountCodes)
            ->where('journal_entries.entry_date', '>=', $start->toDateString())
            ->get(['journal_entries.entry_date', 'journal_lines.debit', 'journal_lines.credit']);

        foreach ($lines as $line) {
            $key = Carbon::parse($line->entry_date)->format('Y-m');
            if ($buckets->has($key)) {
                $bucket = $buckets->get($key);
                $bucket['debit'] += (int) $line->debit;
                $bucket['credit'] += (int) $line->credit;
                $buckets->put($key, $bucket);
            }
        }

        return $buckets->values();
    }

    /**
     * @param  Collection<int, array{key: string, label: string}>  $buckets
     */
    private function periodLabel(Collection $buckets): string
    {
        $first = Carbon::createFromFormat('!Y-m', $buckets->first()['key']);
        $last = Carbon::createFromFormat('!Y-m', $buckets->last()['key']);

        return $first->translatedFormat('M').' – '.$last->translatedFormat('M Y');
    }
}
