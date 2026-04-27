<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        ?User $user,
        string $eventGroup,
        string $action,
        string $description,
        ?Model $auditable = null,
        ?Location $location = null,
        array $meta = [],
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'location_id' => $location?->id,
            'event_group' => $eventGroup,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
