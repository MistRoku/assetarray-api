# Reporting Guide

Read-only analytics. Everything here is gated `manager-or-above`
(movement rows and valuations carry cost data).

Available reports:

- Inventory valuation (`GET /api/v1/reports/inventory-valuation`) —
  on-hand value per product per branch, valued at **cost**, not revenue
- Stock movements (`GET /api/v1/reports/stock-movements`)
- Low stock (`GET /api/v1/reports/low-stock`) — rows below threshold
- Product performance (`GET /api/v1/reports/product-performance`) —
  units sold + revenue from sale movements (recorded totals preferred,
  falling back to current selling price)
- Transfer history (`GET /api/v1/reports/transfers`)

All accept `branch_id` / date filters via query string. Report methods
return full collections (no pagination) — scope filters to bound memory
on large datasets.

All reports support CSV export (streamed, flat memory):

```http
GET /api/v1/reports/export/{type}
```

Supported types:

```txt
inventory-valuation
low-stock
stock-movements
product-performance
transfers
```

Example:

```http
GET /api/v1/reports/export/low-stock
```

Unknown types return `404` (not an empty file).
