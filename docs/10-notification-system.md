# Notification System

AssetArray API includes in-app notifications with an unread workflow.
There is deliberately no cross-user listing — every endpoint is scoped to
the caller.

Notification types:

- `low_stock` — product dropped below threshold (recipients: super-admins
  + the owning branch's manager, resolved by `StockAlertService`)
- `transfer_requested` — transfer awaits destination-side approval
- `purchase_order_received` — goods arrived (partial or complete), sent to
  whoever raised the PO

Endpoints:

```http
GET /api/v1/notifications
GET /api/v1/notifications/unread-count
PUT /api/v1/notifications/{id}/read
PUT /api/v1/notifications/read-all
```

Reading another user's notification id returns `403` even if enumerable.
`read-all` marks the whole inbox in one query (no per-row events).

Notifications are dispatched through queued `ShouldQueue` listeners, so a
slow inbox never delays the stock write that triggered it. With
`QUEUE_CONNECTION=database`, run `php artisan queue:work` — otherwise
notifications pile up in `jobs` (correct behaviour, not a bug).
