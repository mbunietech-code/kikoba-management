<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InsuranceAccount extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'member_id', 'plan_name', 'monthly_contribution', 'coverage_amount', 'start_date', 'end_date', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'monthly_contribution' => 'int', 'coverage_amount' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class); }

    public function contributions() { return $this->hasMany(InsuranceContribution::class); }

    public function claims() { return $this->hasMany(InsuranceClaim::class); }
}
