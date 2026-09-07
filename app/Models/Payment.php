<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'member_id', 'provider', 'payment_method', 'amount', 'currency', 'external_reference', 'internal_reference', 'idempotency_key', 'purpose', 'status', 'paid_at', 'verified_at', 'raw_payload'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'verified_at' => 'datetime', 'raw_payload' => 'array', 'amount' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class)->withTrashed(); }
}
