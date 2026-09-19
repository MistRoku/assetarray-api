# CSV Import and Export

## Product Import

Upload CSV to:

```http
POST /api/v1/products/import
```

Returns `202` immediately — rows are processed by
`ProcessProductCsvImportJob`, never in the request. The file streams (no
whole-file reads), per-row failures are logged and skipped, and the source
file is deleted only on success (kept for inspection on final failure).

Headers:

```csv
name,category,description,cost_price,selling_price,min_stock_threshold,barcode
```

Only `name` is required. Unknown categories are auto-created as active.
Blank-name rows (e.g. trailing newlines) are skipped quietly. File cap:
10 MB, `csv`/`txt`.

## Report Export

Download CSV from:

```http
GET /api/v1/reports/export/{type}
```

Streams with flat memory regardless of row count. Example:

```http
GET /api/v1/reports/export/low-stock
```
