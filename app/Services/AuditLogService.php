<?php

namespace App\Services;

use App\Models\AuditLog;

final class AuditLogService
{
    public function log(
        string $action,
        string $entityType,
        int|string|null $entityId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => app()->bound('request') ? request()->ip() : null,
            'created_at' => now(),
        ]);
    }
}
