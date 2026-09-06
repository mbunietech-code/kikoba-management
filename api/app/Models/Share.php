<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Share extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'member_id', 'quantity', 'price_per_share', 'total_value', 'purchased_at', 'transaction_reference', 'transaction_id', 'status'];

    protected function casts(): array
    {
        return ['purchased_at' => 'date', 'quantity' => 'int', 'price_per_share' => 'int', 'total_value' => 'int'];
    }


    public function member() { return $this->belongsTo(Member::class); }
}
