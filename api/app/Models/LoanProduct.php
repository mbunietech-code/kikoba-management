<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanProduct extends BaseModel
{
    use BelongsToOrganization, HasUuids, SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'description', 'minimum_amount', 'maximum_amount', 'interest_rate', 'interest_method', 'repayment_period', 'repayment_frequency', 'processing_fee', 'insurance_fee', 'penalty_rate', 'minimum_savings', 'minimum_shares', 'required_guarantors', 'status'];

    protected function casts(): array
    {
        return ['interest_rate' => 'float', 'processing_fee' => 'float', 'insurance_fee' => 'float', 'penalty_rate' => 'float', 'minimum_amount' => 'int', 'maximum_amount' => 'int', 'minimum_savings' => 'int', 'repayment_period' => 'int', 'minimum_shares' => 'int', 'required_guarantors' => 'int'];
    }


    public function loans() { return $this->hasMany(Loan::class); }
}
