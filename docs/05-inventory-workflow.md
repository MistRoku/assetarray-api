# Inventory Workflow

Inventory is tracked per product per branch (`stock_levels`, unique on
the pair). Listing is open to all authenticated users (staff need
visibility to count); writes are manager-only.

## Stock Adjustment

```http
POST /api/v1/inventory/adjust
```

Rules:

- Runs inside a database transaction with `lockForUpdate()` — concurrent
  adjustments can't lose updates
- Prevents negative stock unless the reason contains `correction`
- Creates an `adjustment` stock movement (the ledger is append-only)
- Dispatches `CheckLowStockJob` when the new quantity drops below the
  product's `min_stock_threshold`
- Writes an audit log inside the same transaction (rolls back together)

## Movement History

```http
GET /api/v1/inventory/movements
```

Filterable by `branch_id`, `product_id`, `movement_type` (`receipt`,
`sale`, `adjustment`, `transfer_in`, `transfer_out`) and date range.
Newest first.
