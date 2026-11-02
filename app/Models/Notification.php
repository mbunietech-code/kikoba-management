<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'user_id', 'member_id', 'title', 'message', 'type', 'channel', 'status', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /**
     * Notifications a user may see: staff see the whole group's feed; a member
     * sees group-wide announcements plus the ones addressed to them.
     */
    public function scopeVisibleTo(Builder $query, ?User $user, string $orgId): Builder
    {
        $query->where('organization_id', $orgId);

        if ($user?->isStaff()) {
            return $query;
        }

        $memberId = $user?->member?->id;

        return $query->where(fn (Builder $w) => $w
            ->where(fn (Builder $all) => $all->whereNull('member_id')->whereNull('user_id'))
            ->when($memberId, fn (Builder $q) => $q->orWhere('member_id', $memberId))
            ->when($user, fn (Builder $q) => $q->orWhere('user_id', $user->id)));
    }

    public function member()
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
