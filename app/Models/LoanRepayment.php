<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LoanRepayment extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'loan_id', 'member_id', 'schedule_id', 'transaction_id', 'installment_number', 'principal_paid', 'interest_paid', 'fee_paid', 'penalty_paid', 'total_paid', 'payment_date', 'reference', 'method'];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'principal_paid' => 'int', 'interest_paid' => 'int', 'fee_paid' => 'int', 'penalty_paid' => 'int', 'total_paid' => 'int'];
    }


    public function loan() { return $this->belongsTo(Loan::class); }

    public function member() { return $this->belongsTo(Member::class); }
}
