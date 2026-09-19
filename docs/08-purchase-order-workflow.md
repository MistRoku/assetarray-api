# Purchase Order Workflow

Purchase orders manage goods received from suppliers. Goods may arrive in
multiple batches — each receipt books one batch and recomputes the header.

## States

```txt
draft -> sent -> partially_received -> received
draft/sent/partially_received -> cancelled
```

## Create PO

```http
POST /api/v1/purchase-orders
```

Always starts as `draft`. Line totals and the header `total_amount` are
computed server-side, never trusted from the client.

## Show PO

```http
GET /api/v1/purchase-orders/{id}
```

Includes lines with per-line ordered-vs-received progress.

## Send PO

```http
PUT /api/v1/purchase-orders/{id}/send
```

Draft → sent. One-way for this step; from here only receive or cancel.

## Receive Goods

```http
POST /api/v1/purchase-orders/{id}/receive
```

Books one batch: per-line over-receiving is rejected, branch stock is
bumped under lock, and `receipt` movements are written. Fires
`PurchaseOrderReceived` per batch — partial or complete. `received_at` is
stamped only on full completion.

## Cancel PO

```http
PUT /api/v1/purchase-orders/{id}/cancel
```

Stops future receipts; already-received stock stays on the shelves.
Fully received or already-cancelled orders can't be cancelled.
