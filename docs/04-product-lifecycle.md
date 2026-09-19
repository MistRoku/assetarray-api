# Product Lifecycle

Products represent sellable or trackable assets.

## Create Product

```http
POST /api/v1/products
```

SKU is auto-generated (`PRD-XXXXXXXX`, collision-checked including
soft-deleted rows) when omitted. Never recycled.

## Show Product

```http
GET /api/v1/products/{id}
```

Includes category, supplier and per-branch stock levels.

## Update Product

```http
PUT /api/v1/products/{id}
```

Price changes are journaled automatically to `product_price_histories`
by `ProductObserver` — partial changes record only the changed side.

## Soft Delete Product

```http
DELETE /api/v1/products/{id}
```

Manager-level and up. The SKU stays reserved after deletion.

## Price History

```http
GET /api/v1/products/{id}/price-history
```

Manager-only: exposes cost prices. Newest first, timed by `changed_at`.

## CSV Import

```http
POST /api/v1/products/import
```

Queued — returns `202` immediately; rows are processed by
`ProcessProductCsvImportJob`. Per-row failures are logged, never fatal.

Expected CSV headers:

```csv
name,category,description,cost_price,selling_price,min_stock_threshold,barcode
```
