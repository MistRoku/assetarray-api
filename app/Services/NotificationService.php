<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class NotificationService
{
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $user->notifications()
            ->when(isset($filters['unread_only']) && filter_var($filters['unread_only'], FILTER_VALIDATE_BOOLEAN), fn (Builder $q): Builder => $q->unread())
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function unreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    public function markRead(Notification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllRead(User $user): void
    {
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

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
