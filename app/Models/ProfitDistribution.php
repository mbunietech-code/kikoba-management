<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProfitDistribution extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'period_start', 'period_end', 'total_profit', 'reserved_amount', 'distributable_profit', 'basis', 'status', 'distribution_date'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'distribution_date' => 'date', 'total_profit' => 'int', 'reserved_amount' => 'int', 'distributable_profit' => 'int'];
    }


    public function allocations() { return $this->hasMany(ProfitAllocation::class); }
}
