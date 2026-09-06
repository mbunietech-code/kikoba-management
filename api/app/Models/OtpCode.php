<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OtpCode extends BaseModel
{
    use HasUuids;

    protected $fillable = ['user_id', 'destination', 'channel', 'purpose', 'code_hash', 'attempts', 'expires_at', 'consumed_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }


    public function user() { return $this->belongsTo(User::class); }
}
