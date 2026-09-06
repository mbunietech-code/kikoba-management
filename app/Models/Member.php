<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends BaseModel
{
    use BelongsToOrganization, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id', 'user_id', 'member_number', 'full_name', 'phone', 'email',
        'date_of_birth', 'gender', 'address', 'next_of_kin', 'next_of_kin_phone',
        'registration_date', 'status', 'avatar_color',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registration_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function savingsAccount(): HasOne
    {
        return $this->hasOne(SavingsAccount::class);
    }

    public function savingsTransactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function guaranteeing(): HasMany
    {
        return $this->hasMany(Guarantor::class, 'guarantor_member_id');
    }

    public function projectInvestments(): HasMany
    {
        return $this->hasMany(ProjectInvestment::class);
    }

    public function insuranceAccount(): HasOne
    {
        return $this->hasOne(InsuranceAccount::class);
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sharesValue(): int
    {
        return (int) $this->shares()->sum('total_value');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}
