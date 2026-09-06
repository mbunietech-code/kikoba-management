<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class Audit
{
    public static function log(
        ?Request $request,
        string $action,
        string $entity,
        ?string $entityId = null,
        ?array $old = null,
        ?array $new = null,
    ): void {
        AuditLog::create([
            'organization_id' => $request?->user()?->organization_id,
            'user_id' => $request?->user()?->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
