<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Account extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'account_code', 'name', 'account_type', 'parent_id', 'balance', 'status'];

    protected function casts(): array
    {
        return ['balance' => 'int'];
    }


    public function lines() { return $this->hasMany(JournalLine::class); }
}
