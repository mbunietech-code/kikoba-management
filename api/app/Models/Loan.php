<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'organization_id', 'member_id', 'loan_product_id', 'loan_application_id', 'loan_number',
        'principal_amount', 'interest_amount', 'processing_fee', 'insurance_amount', 'penalty_amount',
        'total_amount', 'amount_paid', 'outstanding_balance', 'status', 'purpose', 'period',
        'repayment_frequency', 'interest_method', 'application_date', 'approval_date',
        'disbursement_date', 'maturity_date',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'approval_date' => 'date',
            'disbursement_date' => 'date',
            'maturity_date' => 'date',
            'principal_amount' => 'int',
            'interest_amount' => 'int',
            'processing_fee' => 'int',
            'insurance_amount' => 'int',
            'penalty_amount' => 'int',
            'total_amount' => 'int',
            'amount_paid' => 'int',
            'outstanding_balance' => 'int',
            'period' => 'int',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(LoanRepaymentSchedule::class)->orderBy('installment_number');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }
}
