# AssetArray API

Production-grade Laravel 13 REST API for multi-branch asset and inventory management.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20)
![Database](https://img.shields.io/badge/DB-SQLite%20%7C%20MySQL-4479A1)
![Tests](https://img.shields.io/badge/tests-PHPUnit-67A938)
![Docs](https://img.shields.io/badge/docs-Scribe-orange)
![CI](https://github.com/yourusername/assetarray-api/actions/workflows/ci.yml/badge.svg)

## Purpose

AssetArray API demonstrates enterprise Laravel backend engineering:

- Authentication with Laravel Sanctum
- Role-based authorisation with Policies and Gates
- Asset and inventory management across branches
- Stock transfers and stock takes
- Supplier and purchase order workflows
- Reporting and CSV export
- Immutable audit logging
- Queue-based notifications
- Auto-generated API documentation
- Feature testing

## Tech Stack

- Laravel 13
- PHP 8.3+
- SQLite (local) / MySQL-ready migrations
- Eloquent ORM
- Laravel Sanctum
- Laravel Queue
- Laravel Scribe
- PHPUnit (+ Pint, PHPStan, PHPCS)
- Git + GitHub
- Zed IDE

## Local Setup

### Requirements

- PHP 8.3+
- Composer
- Zed or any code editor
- No MySQL needed locally — SQLite is the default (`DB_CONNECTION=sqlite`)

### Installation

```bash
git clone https://github.com/MistRoku/assetarray-api.git
cd assetarray-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

> Sanctum is already installed — no `install:api` step needed.
> Seeding loads `DemoDataSeeder` (idempotent; refuses production unless
> `ALLOW_DEMO_SEED=true`).

### Queue Worker (notifications)

Queued listeners only run with a worker active.
With `QUEUE_CONNECTION=database`:

```bash
php artisan queue:work
```

Without it, jobs pile up in `jobs` — correct behaviour, not a bug.

### Demo Users

| Email | Password | Role |
|---|---|---|
| admin@assetarray.test | password | Super Admin |
| manager@assetarray.test | password | Branch Manager |
| east.manager@assetarray.test | password | Branch Manager |
| staff@assetarray.test | password | Staff |

## API Docs

Regenerate the guides appendix, then the docs:

```bash
php artisan docs:appendix
php artisan scribe:generate
```

Serve app:

```bash
php artisan serve
```

Visit:

```txt
http://localhost:8000/docs
```

Workflow guides (`transfers`, `stock takes`, `purchase orders`, …) live in
`docs/` and render after the endpoints; the Scribe front page links them.

Machine-readable specs are generated too:

- OpenAPI: `GET /docs.openapi` (also `storage/app/private/scribe/openapi.yaml`)
- Postman: `GET /docs.postman` (also `storage/app/private/scribe/collection.json`)

## Endpoint Index

All endpoints live under `/api/v1` and (except login / password reset)
require `Authorization: Bearer <token>` plus an active account.

| Area | Method & Path |
|---|---|
| Auth | `POST auth/login`, `POST auth/forgot-password`, `POST auth/reset-password` |
| Auth | `POST auth/logout`, `POST auth/refresh`, `GET auth/profile`, `PUT/PATCH auth/profile` |
| Branches | `GET/POST branches`, `GET/PUT/DELETE branches/{branch}`, `POST branches/{branch}/manager` |
| Products | `GET/POST products`, `GET/PUT/DELETE products/{product}`, `GET products/{product}/price-history`, `POST products/import` |
| Inventory | `GET inventory`, `POST inventory/adjust`, `GET inventory/movements` |
| Transfers | `GET/POST inventory/transfers`, `GET inventory/transfers/{transfer}`, `PUT …/approve`, `PUT …/reject`, `PUT …/receive` |
| Stock takes | `GET/POST inventory/stock-take`, `GET …/{stockTake}`, `PUT …/items`, `PUT …/approve`, `GET …/variance-report` |
| Suppliers | `GET/POST suppliers`, `GET/PUT/DELETE suppliers/{supplier}` |
| Purchase orders | `GET/POST purchase-orders`, `GET …/{purchaseOrder}`, `PUT …/send`, `POST …/receive`, `PUT …/cancel` |
| Reports | `GET reports/{inventory-valuation,stock-movements,low-stock,product-performance,transfers}`, `GET reports/export/{type}` |
| Notifications | `GET notifications`, `GET notifications/unread-count`, `PUT notifications/{notification}/read`, `PUT notifications/read-all` |
| Audit logs | `GET audit-logs` (super-admin) |

## Testing & Quality Gates

```bash
php artisan test          # PHPUnit (in-memory sqlite, dev data untouched)
./vendor/bin/pint --test  # Laravel style — must pass
./vendor/bin/phpstan analyse  # Larastan level 5 — must pass
./vendor/bin/phpcs        # PSR-12 aligned with Pint — must pass
```

## Architecture

```txt
app/
├── Console/Commands
├── Http/Controllers/Api/V1
├── Http/Middleware
├── Http/Requests
├── Http/Resources
├── Models
├── Observers
├── Policies
├── Services
├── Jobs
├── Events
├── Listeners
└── Support
```

Cross-cutting rules: services own transactions and row locks; controllers
stay thin (validate → authorize → service → resource); policies own roles;
observers own immutability and price journaling.

## Key Business Flows

### Stock Adjustment

1. Validate request (manager-only)
2. Lock stock row (`lockForUpdate`)
3. Prevent invalid negative stock (unless `correction` reason)
4. Update stock level
5. Create append-only stock movement
6. Dispatch low stock job if threshold breached
7. Write audit log in the same transaction

### Transfer Workflow

1. Create pending transfer (validates source stock, reserves nothing)
2. Notify receiving (destination) manager
3. Approve transfer (destination manager only)
4. Deduct source stock + `transfer_out` movement
5. Receive transfer (destination manager only)
6. Add destination stock + `transfer_in` movement
7. Record audit logs at every step

## Why This Project Matters

This project proves the ability to build a maintainable, secure, testable Laravel backend suitable for real business operations.
