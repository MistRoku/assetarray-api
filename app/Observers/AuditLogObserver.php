<?php

namespace App\Observers;

use App\Models\AuditLog;
use RuntimeException;

/**
 * Single enforcement point for audit immutability: history can only ever
 * be appended, not rewritten — even by admins through Eloquent. (This
 * replaces the model-level booted() guards so the rule lives in exactly
 * one place.) Registered via #[ObservedBy] on the AuditLog model.
 */
class AuditLogObserver
{
    public function updating(AuditLog $auditLog): void
    {
        throw new RuntimeException('Audit logs are immutable.');
    }

    public function deleting(AuditLog $auditLog): void
    {
        throw new RuntimeException('Audit logs are immutable.');
    }
}
