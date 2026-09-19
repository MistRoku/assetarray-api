<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * Single choke point for writing audit trail rows.
 *
 * Every mutating service calls log() inside its DB transaction so the audit
 * row rolls back together with the change it describes — no orphan entries.
 * Safe from queue/CLI: auth()->id() and request()->ip() fall back to null.
 */
final class AuditLogService
{
    /**
     * Append one audit row (created/updated/deleted + before/after snapshots).
     */
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
