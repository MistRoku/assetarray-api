# Stock Take Workflow

Stock takes reconcile physical counts with system quantities. Counting is
open to anyone on the branch (staff do the walking); only the branch's
manager can approve counts into live stock.

## States

```txt
open -> submitted -> approved
```

## Start Stock Take

```http
POST /api/v1/inventory/stock-take
```

Optionally pre-seeds count lines via `product_ids`, snapshotting each
product's current system quantity.

## Submit Counts

```http
PUT /api/v1/inventory/stock-take/{id}/items
```

Upserts per product (`variance = counted − system`), so recounts overwrite
and re-submission is allowed for corrections. Touches no live stock.

## Approve Stock Take

```http
PUT /api/v1/inventory/stock-take/{id}/approve
```

Writes counts into live stock levels with per-product `adjustment`
movements. Uncounted and zero-difference lines are skipped (no noise).
Stamps `completed_at`. Terminal state.

## Variance Report

```http
GET /api/v1/inventory/stock-take/{id}/variance-report
```

Read-only system-vs-counted breakdown for review screens and exports.
