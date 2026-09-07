<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProfitAllocation extends BaseModel
{
    use HasUuids;

    protected $fillable = ['profit_distribution_id', 'member_id', 'basis', 'percentage', 'amount', 'status'];

    protected function casts(): array
    {
        return ['percentage' => 'float', 'amount' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class)->withTrashed(); }

    public function distribution() { return $this->belongsTo(ProfitDistribution::class, 'profit_distribution_id'); }
}
