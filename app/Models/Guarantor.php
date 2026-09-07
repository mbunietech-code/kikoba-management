<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guarantor extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'organization_id', 'loan_id', 'member_id', 'guarantor_member_id',
        'guaranteed_amount', 'status', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'guaranteed_amount' => 'int'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id')->withTrashed();
    }

    public function guarantorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id')->withTrashed();
    }
}
