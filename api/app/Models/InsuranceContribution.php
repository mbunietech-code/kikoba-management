<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InsuranceContribution extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'insurance_account_id', 'member_id', 'amount', 'period', 'transaction_id', 'payment_id', 'paid_on', 'reference'];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class); }

    public function account() { return $this->belongsTo(InsuranceAccount::class, 'insurance_account_id'); }
}
