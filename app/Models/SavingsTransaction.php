<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SavingsTransaction extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $table = 'savings_transactions';

    protected $fillable = ['organization_id', 'savings_account_id', 'member_id', 'transaction_id', 'type', 'amount', 'balance_before', 'balance_after', 'reference', 'reversal_of'];

    protected function casts(): array
    {
        return ['amount' => 'int', 'balance_before' => 'int', 'balance_after' => 'int'];
    }


    public function account() { return $this->belongsTo(SavingsAccount::class, 'savings_account_id'); }

    public function member() { return $this->belongsTo(Member::class)->withTrashed(); }
}
