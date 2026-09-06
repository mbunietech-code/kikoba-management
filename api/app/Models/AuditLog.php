<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLog extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'user_id', 'action', 'entity', 'entity_id', 'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }
}
