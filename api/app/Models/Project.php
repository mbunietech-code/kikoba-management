<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends BaseModel
{
    use BelongsToOrganization, HasUuids, SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'description', 'type', 'capital_required', 'capital_raised', 'expected_profit', 'actual_profit', 'start_date', 'end_date', 'status', 'manager'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'capital_required' => 'int', 'capital_raised' => 'int', 'expected_profit' => 'int', 'actual_profit' => 'int'];
    }


    public function investments() { return $this->hasMany(ProjectInvestment::class); }
}
