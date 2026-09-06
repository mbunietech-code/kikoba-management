<?php

namespace App\Http\Controllers\Api;

use App\Models\ProfitDistribution;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class ProfitDistributionController extends ApiController
{
    public function destroy(Request $request, string $id)
    {
        $dist = $this->find($request, ProfitDistribution::class, $id);
        if (in_array($dist->status, ['approved', 'distributed'])) {
            return ApiResponse::error('LOCKED', 'A distributed allocation cannot be deleted.', 422);
        }
        $dist->allocations()->delete();
        $dist->delete();
        Audit::log($request, 'DELETE_PROFIT_DISTRIBUTION', 'ProfitDistribution', $id);

        return ApiResponse::message('Distribution removed');
    }

    public function index(Request $request)
    {
        return $this->items(
            ProfitDistribution::where('organization_id', $this->orgId($request))->latest('period_end')->get(),
            fn (ProfitDistribution $d) => $this->row($d),
        );
    }

    public function show(Request $request, string $id)
    {
        $d = ProfitDistribution::where('organization_id', $this->orgId($request))
            ->with('allocations.member:id,full_name,member_number,avatar_color')
            ->findOrFail($id);

        return $this->item($d, fn ($d) => [
            ...$this->row($d),
            'allocations' => $d->allocations
                ->sortByDesc('amount')
                ->values()
                ->map(fn ($a) => [
                    'member' => $a->member ? ['id' => $a->member->id, 'fullName' => $a->member->full_name, 'memberNumber' => $a->member->member_number, 'avatarColor' => $a->member->avatar_color] : null,
                    'percentage' => (float) $a->percentage,
                    'amount' => $a->amount,
                    'status' => $a->status,
                ]),
        ]);
    }

    private function row(ProfitDistribution $d): array
    {
        return [
            'id' => $d->id,
            'periodStart' => $d->period_start?->toDateString(),
            'periodEnd' => $d->period_end?->toDateString(),
            'totalProfit' => $d->total_profit,
            'reservedAmount' => $d->reserved_amount,
            'distributableProfit' => $d->distributable_profit,
            'basis' => $d->basis,
            'status' => $d->status,
            'distributionDate' => $d->distribution_date?->toDateString(),
        ];
    }
}
