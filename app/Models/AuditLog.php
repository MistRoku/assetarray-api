<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Immutable audit trail row for entity creates/updates/deletes.
 *
 * Timestamps are managed manually (single created_at) and updates/deletes
 * throw — history can only ever be appended. Write via AuditLogService.
 */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Actor (null for system/queue actions).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enforce append-only semantics at the model layer.
     * Even admins cannot rewrite history through Eloquent.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }
}
