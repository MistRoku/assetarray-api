<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Audit Logs
 *
 * Read-only window into the immutable audit trail. Super-admin only — rows
 * contain before/after snapshots that may include sensitive values.
 *
 * @queryParam entity_type Filter by entity class. Example: App\Models\Product
 * @queryParam entity_id Filter by entity ID. Example: 1
 * @queryParam user_id Filter by user ID. Example: 1
 * @queryParam from Date from. Example: 2026-01-01
 * @queryParam to Date to. Example: 2026-12-31
 */
class AuditLogController extends Controller
{
    /**
     * List audit logs
     *
     * Newest first. All filters are optional and combine with AND.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('super-admin');

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->query('entity_type'), fn ($q, $type) => $q->where('entity_type', $type))
            ->when($request->query('entity_id'), fn ($q, $id) => $q->where('entity_id', $id))
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate((int) $request->query('per_page', 15));

        return AuditLogResource::collection($logs);
    }
}
