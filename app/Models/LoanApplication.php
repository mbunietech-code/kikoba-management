<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LoanApplication extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'member_id', 'loan_product_id', 'requested_amount', 'purpose', 'requested_period', 'repayment_frequency', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'rejection_reason'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'requested_amount' => 'int', 'requested_period' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class)->withTrashed(); }

    public function product() { return $this->belongsTo(LoanProduct::class, 'loan_product_id'); }
}
