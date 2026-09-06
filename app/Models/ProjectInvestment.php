<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectInvestment extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'project_id', 'member_id', 'amount', 'profit_share', 'status', 'invested_at', 'completed_at'];

    protected function casts(): array
    {
        return ['invested_at' => 'date', 'completed_at' => 'date', 'amount' => 'int', 'profit_share' => 'int'];
    }


    public function project() { return $this->belongsTo(Project::class); }

    public function member() { return $this->belongsTo(Member::class); }
}
