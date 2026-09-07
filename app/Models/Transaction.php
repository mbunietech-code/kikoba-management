<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'member_id', 'payment_id', 'transaction_reference', 'type', 'amount', 'currency', 'status', 'description', 'reversal_of', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class)->withTrashed(); }

    public function payment() { return $this->belongsTo(Payment::class); }
}
