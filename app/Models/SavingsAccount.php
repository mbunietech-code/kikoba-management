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

    /**
     * The member's savings account, opened (empty) if they do not have one yet.
     */
    public static function openFor(Member $member): self
    {
        if ($existing = static::where('member_id', $member->id)->first()) {
            return $existing;
        }

        $next = (int) static::where('account_number', 'like', 'SAV-%')->pluck('account_number')
            ->map(fn (string $n) => (int) substr($n, 4))->max() + 1;

        return static::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'account_number' => 'SAV-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
            'balance' => 0,
            'status' => 'active',
            'opened_at' => $member->registration_date ?? now(),
        ]);
    }

    public function member()
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function transactions()
    {
        return $this->hasMany(SavingsTransaction::class);
    }
}
