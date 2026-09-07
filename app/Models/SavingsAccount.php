<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SavingsAccount extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $table = 'savings_accounts';

    protected $fillable = ['organization_id', 'member_id', 'account_number', 'balance', 'status', 'opened_at'];

    protected function casts(): array
    {
        return ['opened_at' => 'date', 'balance' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class)->withTrashed(); }

    public function transactions() { return $this->hasMany(SavingsTransaction::class); }
}
