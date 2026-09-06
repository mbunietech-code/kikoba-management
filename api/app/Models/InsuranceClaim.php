<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InsuranceClaim extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'insurance_account_id', 'member_id', 'claim_number', 'claim_type', 'description', 'amount_requested', 'amount_approved', 'status', 'submitted_at', 'reviewed_at', 'approved_at', 'paid_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'paid_at' => 'datetime', 'amount_requested' => 'int', 'amount_approved' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class); }

    public function account() { return $this->belongsTo(InsuranceAccount::class, 'insurance_account_id'); }
}
