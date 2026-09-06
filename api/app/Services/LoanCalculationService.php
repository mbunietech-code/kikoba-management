<?php

namespace App\Services;

use App\Models\LoanProduct;
use Illuminate\Support\Carbon;

class LoanCalculationService
{
    /**
     * Compute interest, fees, totals and a repayment schedule for a loan.
     *
     * @return array{
     *   interest:int, processing_fee:int, insurance:int, total:int, installment:int,
     *   schedule: array<int, array{installment:int, due_date:string, principal_due:int, interest_due:int, fee_due:int, total_due:int}>
     * }
     */
    public function quote(LoanProduct $product, int $principal, int $period, ?Carbon $start = null): array
    {
        $start ??= now();
        $rate = (float) $product->interest_rate;
        $method = $product->interest_method;

        $interest = $method === 'flat'
            ? (int) round($principal * $rate * $period / (100 * 12))
            : $this->reducingInterest($principal, $rate, $period);

        $fee = (int) round($principal * $product->processing_fee / 100);
        $insurance = (int) round($principal * $product->insurance_fee / 100);
        $total = $principal + $interest + $fee + $insurance;
        $installment = (int) round($total / $period);

        $schedule = [];
        $principalPer = intdiv($principal, $period);
        $interestPer = intdiv($interest, $period);
        $feePer = intdiv($fee + $insurance, $period);

        for ($k = 1; $k <= $period; $k++) {
            $isLast = $k === $period;
            $principalDue = $isLast ? $principal - $principalPer * ($period - 1) : $principalPer;
            $interestDue = $isLast ? $interest - $interestPer * ($period - 1) : $interestPer;
            $feeDue = $isLast ? ($fee + $insurance) - $feePer * ($period - 1) : $feePer;

            $schedule[] = [
                'installment' => $k,
                'due_date' => $start->copy()->addMonths($k)->toDateString(),
                'principal_due' => $principalDue,
                'interest_due' => $interestDue,
                'fee_due' => $feeDue,
                'total_due' => $principalDue + $interestDue + $feeDue,
            ];
        }

        return compact('interest', 'fee', 'insurance', 'total', 'installment', 'schedule') + [
            'processing_fee' => $fee,
        ];
    }

    private function reducingInterest(int $principal, float $annualRate, int $period): int
    {
        $monthly = $annualRate / 100 / 12;
        $balance = $principal;
        $totalInterest = 0;
        $principalPer = $principal / $period;

        for ($k = 0; $k < $period; $k++) {
            $totalInterest += $balance * $monthly;
            $balance -= $principalPer;
        }

        return (int) round($totalInterest);
    }
}
