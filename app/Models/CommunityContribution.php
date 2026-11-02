<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CommunityContributionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A member's payment into the group's Jamii (social) or Dharura (emergency) fund.
 */
class CommunityContribution extends BaseModel
{
    /** @use HasFactory<CommunityContributionFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public const FUNDS = ['jamii', 'dharura'];

    /** Standard monthly amount when the group has not set its own. */
    public const DEFAULT_AMOUNT = 5_000;

    protected $fillable = ['organization_id', 'member_id', 'fund', 'amount', 'period', 'paid_on', 'reference', 'transaction_id', 'status', 'reversal_reason', 'recorded_by'];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'int'];
    }

    /** Contributions that still count (not reversed). */
    public function scopeCounted(Builder $query): Builder
    {
        return $query->where('status', 'recorded');
    }

    public function member()
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
