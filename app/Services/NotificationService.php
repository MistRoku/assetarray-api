<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Per-user notification inbox: listing, unread counts and read marking.
 */
final class NotificationService
{
    /** Paginated inbox, newest first; unread_only narrows to unreads. */
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $user->notifications()
            ->when(isset($filters['unread_only']) && filter_var($filters['unread_only'], FILTER_VALIDATE_BOOLEAN), fn (Builder $q): Builder => $q->unread())
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /** Badge count for the inbox. */
    public function unreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * Mark one notification read. Idempotent — already-read rows are
     * skipped to avoid a needless write (and updated_at touch).
     */
    public function markRead(Notification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    /**
     * Mark the whole inbox read in a single query — no per-row events.
     * (Audit logging is intentionally skipped here: high volume, low value.)
     */
    public function markAllRead(User $user): void
    {
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Create a notification for a user. $data is a free-form payload
     * (ids, links) cast to array — keep it small and JSON-safe.
     */
    public function createForUser(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
