<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RefreshToken extends BaseModel
{
    use HasUuids;

    protected $fillable = ['user_id', 'token_hash', 'device', 'ip_address', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }


    public function user() { return $this->belongsTo(User::class); }
}
