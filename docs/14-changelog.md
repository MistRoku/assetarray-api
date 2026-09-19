# Changelog

## 1.0.0 — 2026-09-19

Initial release of AssetArray API.

Features:

- Sanctum authentication (login/logout/refresh, password reset, profiles)
- Role-based authorisation (policies + `manager-or-above` / `super-admin` gates)
- Branch management (super-admin writes, manager assignment)
- Product catalogue (auto SKUs, price history, CSV import)
- Inventory adjustments (row-locked, ledger-backed, low-stock jobs)
- Stock transfers (request → approve → receive / reject)
- Stock takes (open → submit → approve, variance reports)
- Suppliers
- Purchase orders (draft → sent → partial/full receipt, cancel)
- Reports (valuation, movements, low stock, performance, transfers + CSV export)
- In-app notifications (queued listeners, unread workflow)
- Immutable audit logs
- Scribe documentation + workflow guides (`docs/`)
- PHPUnit tests (smoke + factory regression)
