<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Notifications
 *
 * Per-user inbox. All endpoints are implicitly scoped to the caller — there
 * is deliberately no "list everyone's notifications" action.
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * List notifications
     *
     * Newest first; unread_only narrows to unreads via query string.
     */
    public function index(Request $request): ResourceCollection
    {
        $notifications = $this->notificationService->list($request->user(), $request->all());

        return NotificationResource::collection($notifications);
    }

    /**
     * Unread count
     *
     * Badge number for the inbox.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'unread_count' => $this->notificationService->unreadCount($request->user()),
            ],
        ]);
    }

    /**
     * Mark notification as read
     *
     * Ownership enforced: reading another user's id 403s even if enumerable.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $this->notificationService->markRead($notification);

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read
     *
     * Single bulk query (no per-row events); only touches the caller's rows.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllRead($request->user());

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }
}
