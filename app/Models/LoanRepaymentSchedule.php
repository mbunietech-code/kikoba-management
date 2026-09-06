<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LoanRepaymentSchedule extends BaseModel
{
    use HasUuids;

    protected $table = 'loan_repayment_schedules';

    protected $fillable = ['loan_id', 'installment_number', 'due_date', 'principal_due', 'interest_due', 'fee_due', 'penalty_due', 'total_due', 'amount_paid', 'status', 'paid_at'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'paid_at' => 'date', 'installment_number' => 'int', 'principal_due' => 'int', 'interest_due' => 'int', 'fee_due' => 'int', 'penalty_due' => 'int', 'total_due' => 'int', 'amount_paid' => 'int'];
    }


    public function loan() { return $this->belongsTo(Loan::class); }
}
