<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'user_id', 'member_id', 'title', 'message', 'type', 'channel', 'status', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }


    public function member() { return $this->belongsTo(Member::class); }

    public function user() { return $this->belongsTo(User::class); }
}
